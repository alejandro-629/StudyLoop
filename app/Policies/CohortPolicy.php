<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\CohortRole;
use App\Models\Cohort;
use App\Models\User;

/**
 * Cohort access. Org admins manage every cohort in their organization;
 * instructors manage their own cohorts; TAs and learners only see the
 * cohorts they belong to. Nothing crosses organizations.
 */
final class CohortPolicy
{
    public function view(User $user, Cohort $cohort): bool
    {
        return $user->cohortRole($cohort) !== null
            || $user->isOrganizationAdmin($cohort->organizationId());
    }

    public function manageInvitations(User $user, Cohort $cohort): bool
    {
        return $user->isOrganizationAdmin($cohort->organizationId())
            || $user->cohortRole($cohort) === CohortRole::Instructor;
    }

    /**
     * Invite someone with a specific role. Instructors may only invite TAs
     * and learners; only org admins can appoint instructors.
     */
    public function invite(User $user, Cohort $cohort, CohortRole $role): bool
    {
        if ($user->isOrganizationAdmin($cohort->organizationId())) {
            return true;
        }

        return $user->cohortRole($cohort) === CohortRole::Instructor
            && in_array($role, CohortRole::invitableByInstructors(), true);
    }
}
