<?php

declare(strict_types=1);

use App\Enums\CohortRole;
use App\Models\Cohort;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('seeds one synthetic account per role and can be re-run', function (): void {
    config(['studyloop.demo_password' => 'demo-password-123']);

    $this->seed();
    $this->seed();

    expect(User::query()->count())->toBe(5);

    $learner = User::query()->where('email', 'learner@studyloop.test')->sole();
    $instructor = User::query()->where('email', 'instructor@studyloop.test')->sole();
    $sectionA = Cohort::query()->where('name', 'Spring 2027 · Section A')->sole();
    $evening = Cohort::query()->where('name', 'Spring 2027 · Evening')->sole();

    expect(Hash::check('demo-password-123', $learner->password))->toBeTrue()
        ->and($learner->cohortRole($sectionA))->toBe(CohortRole::Learner)
        ->and($instructor->cohortRole($sectionA))->toBe(CohortRole::Instructor)
        ->and($learner->can('view', $evening))->toBeFalse()
        ->and(User::query()->where('email', 'admin@studyloop.test')->sole()->can('invite', [$sectionA, CohortRole::Instructor]))->toBeTrue();
});
