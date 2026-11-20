<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Cohorts\AcceptCohortInvitation;
use App\Actions\Cohorts\InvitationUnavailable;
use App\Models\Cohort;
use App\Models\CohortInvitation;
use App\Models\Organization;
use App\Models\User;
use App\Support\CurrentOrganization;
use App\Support\InvitationLinks;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

/**
 * The public side of an invitation link. Both routes sit behind the
 * `signed:relative` middleware, so a tampered or expired link never gets here.
 */
final class InvitationController extends Controller
{
    public function show(Request $request, CohortInvitation $invitation): View|RedirectResponse|Response
    {
        if (! $invitation->isPending()) {
            return $this->unavailable(InvitationUnavailable::for($invitation));
        }

        /** @var User|null $user */
        $user = $request->user();

        if ($user !== null && ! $invitation->isFor($user)) {
            return $this->unavailable(InvitationUnavailable::wrongAccount());
        }

        $emailHasAccount = User::query()->where('email', $invitation->email)->exists();

        if ($user === null && $emailHasAccount) {
            // Sign in first; the intended URL brings them back to this link.
            return redirect()->guest(route('login'));
        }

        $cohort = Cohort::query()->with('course.organization')->findOrFail($invitation->cohort_id);

        return view($user === null ? 'invitations.register' : 'invitations.accept', [
            'invitation' => $invitation,
            'cohort' => $cohort,
            'acceptUrl' => InvitationLinks::path($invitation, 'invitations.accept'),
        ]);
    }

    public function accept(
        Request $request,
        CohortInvitation $invitation,
        AcceptCohortInvitation $accept,
        CurrentOrganization $current,
    ): RedirectResponse|Response {
        /** @var User|null $user */
        $user = $request->user();

        try {
            if ($user !== null) {
                $accept->handle($invitation, $user);
            } else {
                $validated = $request->validate([
                    'name' => ['required', 'string', 'max:255'],
                    'password' => ['required', 'confirmed', Password::defaults()],
                ]);

                if (User::query()->where('email', $invitation->email)->exists()) {
                    return redirect()->guest(route('login'));
                }

                $user = DB::transaction(function () use ($invitation, $validated, $accept): User {
                    $user = User::query()->create([
                        'name' => $validated['name'],
                        'email' => mb_strtolower($invitation->email),
                        'password' => $validated['password'],
                    ]);
                    // Opening the emailed link proves the address.
                    $user->forceFill(['email_verified_at' => now()])->save();

                    $accept->handle($invitation, $user);

                    return $user;
                });

                Auth::login($user);
                $request->session()->regenerate();
            }
        } catch (InvitationUnavailable $e) {
            return $this->unavailable($e);
        }

        // Land in the organization of the cohort just joined.
        $organizationId = Cohort::query()->findOrFail($invitation->cohort_id)->organizationId();
        $current->switchTo($user, Organization::query()->findOrFail($organizationId));

        return redirect()
            ->route('dashboard')
            ->with('status', __('You have joined the cohort.'));
    }

    private function unavailable(InvitationUnavailable $reason): Response
    {
        return response()->view('invitations.unavailable', ['reason' => $reason->getMessage()], $reason->status);
    }
}
