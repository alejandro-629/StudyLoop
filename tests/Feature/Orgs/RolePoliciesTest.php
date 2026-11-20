<?php

declare(strict_types=1);

use App\Enums\CohortRole;
use App\Enums\OrganizationRole;
use App\Models\Course;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lets org admins invite every cohort role', function (): void {
    ['admin' => $admin, 'cohort' => $cohort] = cohortWithStaff();

    foreach (CohortRole::cases() as $role) {
        expect($admin->can('invite', [$cohort, $role]))->toBeTrue();
    }
    expect($admin->can('manageInvitations', $cohort))->toBeTrue()
        ->and($admin->can('view', $cohort))->toBeTrue();
});

it('lets instructors invite TAs and learners but not other instructors', function (): void {
    ['instructor' => $instructor, 'cohort' => $cohort] = cohortWithStaff();

    expect($instructor->can('manageInvitations', $cohort))->toBeTrue()
        ->and($instructor->can('invite', [$cohort, CohortRole::Learner]))->toBeTrue()
        ->and($instructor->can('invite', [$cohort, CohortRole::TeachingAssistant]))->toBeTrue()
        ->and($instructor->can('invite', [$cohort, CohortRole::Instructor]))->toBeFalse();
});

it('does not let TAs or learners manage invitations', function (string $who): void {
    $staff = cohortWithStaff();
    $user = $staff[$who];

    expect($user->can('view', $staff['cohort']))->toBeTrue()
        ->and($user->can('manageInvitations', $staff['cohort']))->toBeFalse();

    foreach (CohortRole::cases() as $role) {
        expect($user->can('invite', [$staff['cohort'], $role]))->toBeFalse();
    }
})->with(['ta', 'learner']);

it('gives an instructor no rights over another cohort of the same course', function (): void {
    ['instructor' => $instructor, 'course' => $course] = cohortWithStaff();
    $otherCohort = App\Models\Cohort::factory()->for($course)->create();

    expect($instructor->can('view', $otherCohort))->toBeFalse()
        ->and($instructor->can('manageInvitations', $otherCohort))->toBeFalse();
});

it('keeps cohorts and courses invisible across organizations', function (): void {
    ['cohort' => $cohort, 'course' => $course] = cohortWithStaff();

    $otherOrg = Organization::factory()->create();
    $otherAdmin = User::factory()->create();
    $otherOrg->members()->attach($otherAdmin, ['role' => OrganizationRole::Admin->value]);

    expect($otherAdmin->can('view', $cohort))->toBeFalse()
        ->and($otherAdmin->can('manageInvitations', $cohort))->toBeFalse()
        ->and($otherAdmin->can('invite', [$cohort, CohortRole::Learner]))->toBeFalse()
        ->and($otherAdmin->can('view', $course))->toBeFalse()
        ->and($otherAdmin->can('update', $course))->toBeFalse();
});

it('lets only org admins create and edit courses and manage the organization', function (): void {
    ['organization' => $organization, 'course' => $course, 'admin' => $admin, 'instructor' => $instructor] = cohortWithStaff();

    expect($admin->can('create', [Course::class, $organization]))->toBeTrue()
        ->and($admin->can('update', $course))->toBeTrue()
        ->and($admin->can('manage', $organization))->toBeTrue()
        ->and($instructor->can('view', $course))->toBeTrue()
        ->and($instructor->can('view', $organization))->toBeTrue()
        ->and($instructor->can('create', [Course::class, $organization]))->toBeFalse()
        ->and($instructor->can('update', $course))->toBeFalse()
        ->and($instructor->can('manage', $organization))->toBeFalse();
});

it('rejects duplicate memberships, enrollments, course codes and cohort names', function (): void {
    ['organization' => $organization, 'course' => $course, 'cohort' => $cohort, 'learner' => $learner] = cohortWithStaff();

    expect(fn () => $organization->members()->attach($learner, ['role' => OrganizationRole::Member->value]))
        ->toThrow(UniqueConstraintViolationException::class);
    expect(fn () => $cohort->members()->attach($learner, ['role' => CohortRole::Instructor->value]))
        ->toThrow(UniqueConstraintViolationException::class);
    expect(fn () => Course::factory()->for($organization)->create(['code' => $course->code]))
        ->toThrow(UniqueConstraintViolationException::class);
    expect(fn () => App\Models\Cohort::factory()->for($course)->create(['name' => $cohort->name]))
        ->toThrow(UniqueConstraintViolationException::class);
});
