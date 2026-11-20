<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use App\Support\CurrentOrganization;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Organization overview for org admins: every course and cohort with its
 * size, and a way into each cohort's invitations.
 */
final class OrganizationController extends Controller
{
    public function show(Request $request, Organization $organization, CurrentOrganization $current): View
    {
        Gate::authorize('manage', $organization);

        /** @var User $user */
        $user = $request->user();

        // Opening an organization's page makes it the current one, so the
        // header and navigation match what is on screen.
        $current->switchTo($user, $organization);

        return view('organizations.show', [
            'organization' => $organization,
            'courses' => $organization->courses()
                ->with(['cohorts' => fn ($query) => $query->withCount('members')->orderBy('name')])
                ->orderBy('code')
                ->get(),
        ]);
    }
}
