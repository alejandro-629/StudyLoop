<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Cohorts\InviteToCohort;
use App\Enums\CohortRole;
use App\Models\Cohort;
use App\Models\CohortInvitation;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Invitation management for one cohort: list pending links, send a new
 * one, withdraw one. Org admins and the cohort's instructors only.
 */
final class CohortInvitationController extends Controller
{
    public function index(Request $request, Cohort $cohort): View
    {
        Gate::authorize('manageInvitations', $cohort);

        /** @var User $user */
        $user = $request->user();

        $cohort->load('course');

        return view('cohorts.invitations.index', [
            'cohort' => $cohort,
            'invitations' => $cohort->invitations()->pending()->latest()->get(),
            'roles' => array_values(array_filter(
                CohortRole::cases(),
                fn (CohortRole $role): bool => $user->can('invite', [$cohort, $role]),
            )),
        ]);
    }

    public function store(Request $request, Cohort $cohort, InviteToCohort $invite): RedirectResponse
    {
        Gate::authorize('manageInvitations', $cohort);

        $validated = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            'role' => ['required', Rule::enum(CohortRole::class)],
        ]);

        /** @var User $user */
        $user = $request->user();

        $invite->handle($user, $cohort, $validated['email'], CohortRole::from($validated['role']));

        return redirect()
            ->route('cohorts.invitations.index', $cohort)
            ->with('status', __('Invitation sent to :email.', ['email' => mb_strtolower(trim($validated['email']))]));
    }

    public function destroy(Cohort $cohort, CohortInvitation $invitation): RedirectResponse
    {
        Gate::authorize('manageInvitations', $cohort);

        if ($invitation->isPending()) {
            $invitation->forceFill(['revoked_at' => now()])->save();
        }

        return redirect()
            ->route('cohorts.invitations.index', $cohort)
            ->with('status', __('Invitation withdrawn.'));
    }
}
