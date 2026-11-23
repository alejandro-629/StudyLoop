<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

final class OrganizationPolicy
{
    public function view(User $user, Organization $organization): bool
    {
        return $user->organizationRole($organization) !== null;
    }

    /**
     * Rename the organization, create courses and cohorts, manage members.
     */
    public function manage(User $user, Organization $organization): bool
    {
        return $user->isOrganizationAdmin($organization);
    }
}
