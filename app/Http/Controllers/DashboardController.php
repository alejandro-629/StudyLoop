<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\CurrentOrganization;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class DashboardController extends Controller
{
    /**
     * The signed-in user's cohorts in the current organization only.
     */
    public function __invoke(Request $request, CurrentOrganization $current): View
    {
        /** @var User $user */
        $user = $request->user();

        $organization = $current->for($user);

        return view('dashboard', [
            'organization' => $organization,
            'cohorts' => $organization === null
                ? collect()
                : $user->cohorts()
                    ->whereRelation('course', 'organization_id', $organization->id)
                    ->with('course.organization')
                    ->orderBy('cohorts.name')
                    ->get(),
        ]);
    }
}
