<?php

declare(strict_types=1);

use App\Actions\Cohorts\InviteToCohort;
use App\Enums\CohortRole;
use App\Support\InvitationLinks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // The browser and the app share sessions through cookies, so keep them
    // somewhere that outlives a single request.
    config(['session.driver' => 'file']);
});

it('takes an invited learner from the emailed link to their first sign-in', function (): void {
    Notification::fake();

    ['instructor' => $instructor, 'cohort' => $cohort] = cohortWithStaff();
    $invitation = app(InviteToCohort::class)
        ->handle($instructor, $cohort, 'nia.new@example.test', CohortRole::Learner);

    // Open the link from the email and create the account.
    visit(InvitationLinks::path($invitation))
        ->assertSee('Join '.$cohort->name)
        ->assertSee('Creating an account for nia.new@example.test')
        ->fill('name', 'Nia New')
        ->fill('password', 'study-every-day-42')
        ->fill('password_confirmation', 'study-every-day-42')
        ->press('Create account and join')
        ->assertPathIs('/dashboard')
        ->assertSee('You have joined the cohort.')
        ->assertSee($cohort->name)
        ->assertSee('Learner')
        ->assertDontSee('Invitations')
        ->press('Sign out')
        ->assertPathIs('/');

    // The link is spent.
    visit(InvitationLinks::path($invitation))
        ->assertSee('already been used');

    // First sign-in with the new password.
    visit('/login')
        ->fill('email', 'nia.new@example.test')
        ->fill('password', 'study-every-day-42')
        ->press('Sign in')
        ->assertPathIs('/dashboard')
        ->assertSee($cohort->name)
        ->assertNoJavaScriptErrors();
});
