<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Course;
use App\Models\Organization;
use App\Models\User;

final class CoursePolicy
{
    public function view(User $user, Course $course): bool
    {
        return $user->organizationRole($course->organization_id) !== null;
    }

    public function create(User $user, Organization $organization): bool
    {
        return $user->isOrganizationAdmin($organization);
    }

    public function update(User $user, Course $course): bool
    {
        return $user->isOrganizationAdmin($course->organization_id);
    }
}
