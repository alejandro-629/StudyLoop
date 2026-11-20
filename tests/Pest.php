<?php

declare(strict_types=1);

use App\Enums\CohortRole;
use App\Enums\OrganizationRole;
use App\Models\Cohort;
use App\Models\Course;
use App\Models\Organization;
use App\Models\User;
use Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature');

/**
 * One organization with one course and cohort, an org admin and one
 * enrolled user per cohort role.
 *
 * @return array{organization: Organization, course: Course, cohort: Cohort, admin: User, instructor: User, ta: User, learner: User}
 */
function cohortWithStaff(): array
{
    $organization = Organization::factory()->create();
    $course = Course::factory()->for($organization)->create();
    $cohort = Cohort::factory()->for($course)->create();

    $admin = User::factory()->create();
    $organization->members()->attach($admin, ['role' => OrganizationRole::Admin->value]);

    $people = [];
    foreach (['instructor' => CohortRole::Instructor, 'ta' => CohortRole::TeachingAssistant, 'learner' => CohortRole::Learner] as $key => $role) {
        $user = User::factory()->create();
        $organization->members()->attach($user, ['role' => OrganizationRole::Member->value]);
        $cohort->members()->attach($user, ['role' => $role->value]);
        $people[$key] = $user;
    }

    return [
        'organization' => $organization,
        'course' => $course,
        'cohort' => $cohort,
        'admin' => $admin,
        'instructor' => $people['instructor'],
        'ta' => $people['ta'],
        'learner' => $people['learner'],
    ];
}
