<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists only the signed-in user\'s cohorts with their role', function (): void {
    ['learner' => $learner, 'cohort' => $cohort] = cohortWithStaff();
    $otherCohort = cohortWithStaff()['cohort'];

    $this->actingAs($learner)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee($cohort->name)
        ->assertSee('Learner')
        ->assertDontSee($otherCohort->name)
        ->assertDontSee(route('cohorts.invitations.index', $cohort));
});

it('links instructors to invitation management', function (): void {
    ['instructor' => $instructor, 'cohort' => $cohort] = cohortWithStaff();

    $this->actingAs($instructor)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Instructor')
        ->assertSee(route('cohorts.invitations.index', $cohort));
});
