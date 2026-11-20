<?php

declare(strict_types=1);

use App\Enums\CohortRole;
use App\Models\CohortInvitation;
use App\Notifications\CohortInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(fn () => Notification::fake());

it('lets an instructor invite a learner and emails a signed link', function (): void {
    ['instructor' => $instructor, 'cohort' => $cohort] = cohortWithStaff();

    $this->actingAs($instructor)
        ->post(route('cohorts.invitations.store', $cohort), ['email' => '  New.Learner@Example.com ', 'role' => 'learner'])
        ->assertRedirect(route('cohorts.invitations.index', $cohort))
        ->assertSessionHasNoErrors();

    $invitation = CohortInvitation::query()->sole();
    expect($invitation->email)->toBe('new.learner@example.com')
        ->and($invitation->role)->toBe(CohortRole::Learner)
        ->and($invitation->invited_by)->toBe($instructor->id)
        ->and($invitation->isPending())->toBeTrue()
        ->and($invitation->expires_at->greaterThan(now()->addDays(6)))->toBeTrue();

    Notification::assertSentOnDemand(
        CohortInvitationNotification::class,
        function (CohortInvitationNotification $notification, array $channels, AnonymousNotifiable $notifiable) use ($invitation): bool {
            return $notifiable->routes['mail'] === 'new.learner@example.com'
                && $notification->invitation->is($invitation)
                && str_contains($notification->url(), 'signature=')
                && str_contains($notification->url(), 'expires=');
        },
    );
});

it('refuses to let an instructor appoint another instructor', function (): void {
    ['instructor' => $instructor, 'cohort' => $cohort] = cohortWithStaff();

    $this->actingAs($instructor)
        ->post(route('cohorts.invitations.store', $cohort), ['email' => 'friend@example.com', 'role' => 'instructor'])
        ->assertForbidden();

    expect(CohortInvitation::query()->count())->toBe(0);
    Notification::assertNothingSent();
});

it('lets an org admin invite an instructor', function (): void {
    ['admin' => $admin, 'cohort' => $cohort] = cohortWithStaff();

    $this->actingAs($admin)
        ->post(route('cohorts.invitations.store', $cohort), ['email' => 'co.teacher@example.com', 'role' => 'instructor'])
        ->assertRedirect();

    expect(CohortInvitation::query()->sole()->role)->toBe(CohortRole::Instructor);
});

it('only offers the roles the inviter may hand out', function (): void {
    ['instructor' => $instructor, 'cohort' => $cohort] = cohortWithStaff();

    $this->actingAs($instructor)
        ->get(route('cohorts.invitations.index', $cohort))
        ->assertOk()
        ->assertSee('value="learner"', false)
        ->assertSee('value="ta"', false)
        ->assertDontSee('value="instructor"', false);
});

it('hides invitation management from TAs, learners, other orgs and guests', function (): void {
    $staff = cohortWithStaff();
    $outsider = cohortWithStaff()['admin'];

    foreach ([$staff['ta'], $staff['learner'], $outsider] as $user) {
        $this->actingAs($user)->get(route('cohorts.invitations.index', $staff['cohort']))->assertForbidden();
        $this->actingAs($user)
            ->post(route('cohorts.invitations.store', $staff['cohort']), ['email' => 'x@example.com', 'role' => 'learner'])
            ->assertForbidden();
    }

    auth()->logout();
    $this->get(route('cohorts.invitations.index', $staff['cohort']))->assertRedirect(route('login'));
});

it('does not invite someone who is already in the cohort', function (): void {
    ['instructor' => $instructor, 'learner' => $learner, 'cohort' => $cohort] = cohortWithStaff();

    $this->actingAs($instructor)
        ->post(route('cohorts.invitations.store', $cohort), ['email' => $learner->email, 'role' => 'learner'])
        ->assertSessionHasErrors('email');

    Notification::assertNothingSent();
});

it('validates the email and role', function (): void {
    ['instructor' => $instructor, 'cohort' => $cohort] = cohortWithStaff();

    $this->actingAs($instructor)
        ->post(route('cohorts.invitations.store', $cohort), ['email' => 'not-an-email', 'role' => 'owner'])
        ->assertSessionHasErrors(['email', 'role']);
});

it('withdraws the previous link when the same address is invited again', function (): void {
    ['instructor' => $instructor, 'cohort' => $cohort] = cohortWithStaff();

    foreach ([1, 2] as $attempt) {
        $this->actingAs($instructor)
            ->post(route('cohorts.invitations.store', $cohort), ['email' => 'again@example.com', 'role' => 'learner']);
    }

    [$first, $second] = CohortInvitation::query()->orderBy('id')->get()->all();
    expect($first->isRevoked())->toBeTrue()
        ->and($second->isPending())->toBeTrue();
});

it('withdraws an invitation, scoped to its own cohort', function (): void {
    ['instructor' => $instructor, 'cohort' => $cohort] = cohortWithStaff();
    $invitation = CohortInvitation::factory()->for($cohort)->create();
    $foreign = CohortInvitation::factory()->create();

    $this->actingAs($instructor)
        ->delete(route('cohorts.invitations.destroy', [$cohort, $invitation]))
        ->assertRedirect(route('cohorts.invitations.index', $cohort));
    expect($invitation->refresh()->isRevoked())->toBeTrue();

    $this->actingAs($instructor)
        ->delete("/cohorts/{$cohort->id}/invitations/{$foreign->id}")
        ->assertNotFound();
    expect($foreign->refresh()->isPending())->toBeTrue();
});
