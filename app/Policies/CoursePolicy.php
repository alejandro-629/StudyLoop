<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\CohortRole;
use App\Models\Course;
use App\Models\Organization;
use App\Models\User;

/**
 * Course access. Course material is staff-only: org admins and the
 * course's instructors upload it, TAs may read it, learners never see the
 * raw material (only published items, later).
 */
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

    public function manageMaterials(User $user, Course $course): bool
    {
        return $user->isOrganizationAdmin($course->organization_id)
            || $user->hasCourseRole($course, CohortRole::Instructor);
    }

    public function viewMaterials(User $user, Course $course): bool
    {
        return $user->isOrganizationAdmin($course->organization_id)
            || $user->hasCourseRole($course, CohortRole::Instructor, CohortRole::TeachingAssistant);
    }
}
