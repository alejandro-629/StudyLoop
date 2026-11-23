<?php

declare(strict_types=1);

use App\Enums\CohortRole;
use App\Enums\OrganizationRole;
use App\Models\CohortInvitation;
use App\Models\User;
use App\Support\InvitationLinks;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function invitationUrl(CohortInvitation $invitation, string $route = 'invitations.show'): string
{
    return InvitationLinks::path($invitation, $route);
}

/**
 * @return array{invitation: CohortInvitation, staff: array<string, mixed>}
 */
function pendingInvitation(string $email = 'new.learner@example.com', CohortRole $role = CohortRole::Learner): array
{
    $staff = cohortWithStaff();
    $invitation = CohortInvitation::factory()->for($staff['cohort'])->create([
        'email' => $email,
        'role' => $role,
        'invited_by' => $staff['instructor']->id,
    ]);

    return ['invitation' => $invitation, 'staff' => $staff];
}

it('rejects links without a valid signature', function (): void {
    ['invitation' => $invitation] = pendingInvitation();

    $this->get("/invitations/{$invitation->id}")->assertForbidden();
    $this->get(invitationUrl($invitation).'x')->assertForbidden();
    $this->post("/invitations/{$invitation->id}/accept", [])->assertForbidden();
});

it('lets a new person create an account and join from the link', function (): void {
    ['invitation' => $invitation, 'staff' => $staff] = pendingInvitation();

    $this->get(invitationUrl($invitation))
        ->assertOk()
        ->assertSee('Create account and join')
        ->assertSee('new.learner@example.com');

    $this->post(invitationUrl($invitation, 'invitations.accept'), [
        'name' => 'Nia Newcomer',
        'password' => 'correct horse battery staple',
        'password_confirmation' => 'correct horse battery staple',
    ])->assertRedirect(route('dashboard'));

    $user = User::query()->where('email', 'new.learner@example.com')->sole();
    $this->assertAuthenticatedAs($user);

    expect($user->email_verified_at)->not->toBeNull()
        ->and($user->cohortRole($staff['cohort']))->toBe(CohortRole::Learner)
        ->and($user->organizationRole($staff['organization']))->toBe(OrganizationRole::Member)
        ->and($invitation->refresh()->accepted_by)->toBe($user->id);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee($staff['cohort']->name)
        ->assertSee('Learner');
});

it('requires a name and a confirmed password to create the account', function (): void {
    ['invitation' => $invitation] = pendingInvitation();

    $this->from(invitationUrl($invitation))
        ->post(invitationUrl($invitation, 'invitations.accept'), ['password' => 'short', 'password_confirmation' => 'other'])
        ->assertSessionHasErrors(['name', 'password']);

    expect(User::query()->where('email', 'new.learner@example.com')->exists())->toBeFalse()
        ->and($invitation->refresh()->isPending())->toBeTrue();
});

it('works only once', function (): void {
    ['invitation' => $invitation] = pendingInvitation();
    $user = User::factory()->create(['email' => 'new.learner@example.com']);

    $this->actingAs($user)->post(invitationUrl($invitation, 'invitations.accept'))->assertRedirect(route('dashboard'));
    $this->actingAs($user)->post(invitationUrl($invitation, 'invitations.accept'))->assertStatus(410);
    $this->actingAs($user)->get(invitationUrl($invitation))->assertStatus(410)->assertSee('already been used');
});

it('stops working when it expires', function (): void {
    ['invitation' => $invitation] = pendingInvitation();
    $url = invitationUrl($invitation);

    $this->travelTo($invitation->expires_at->addMinute());

    $this->get($url)->assertForbidden();
    $this->post(invitationUrl($invitation, 'invitations.accept'), [
        'name' => 'Late Learner',
        'password' => 'correct horse battery staple',
        'password_confirmation' => 'correct horse battery staple',
    ])->assertForbidden();

    expect(User::query()->where('email', 'new.learner@example.com')->exists())->toBeFalse();
});

it('stops working when withdrawn', function (): void {
    ['invitation' => $invitation] = pendingInvitation();
    $url = invitationUrl($invitation);
    $invitation->forceFill(['revoked_at' => now()])->save();

    $this->get($url)->assertStatus(410)->assertSee('withdrawn');
});

it('cannot be used by a different signed-in account', function (): void {
    ['invitation' => $invitation, 'staff' => $staff] = pendingInvitation();
    $someoneElse = User::factory()->create();

    $this->actingAs($someoneElse)->get(invitationUrl($invitation))->assertForbidden();
    $this->actingAs($someoneElse)->post(invitationUrl($invitation, 'invitations.accept'))->assertForbidden();

    expect($someoneElse->cohortRole($staff['cohort']))->toBeNull()
        ->and($invitation->refresh()->isPending())->toBeTrue();
});

it('sends people who already have an account to sign in first', function (): void {
    ['invitation' => $invitation] = pendingInvitation();
    User::factory()->create(['email' => 'new.learner@example.com']);

    $this->get(invitationUrl($invitation))->assertRedirect(route('login'));
    $this->post(invitationUrl($invitation, 'invitations.accept'), [
        'name' => 'Takeover Attempt',
        'password' => 'correct horse battery staple',
        'password_confirmation' => 'correct horse battery staple',
    ])->assertRedirect(route('login'));

    expect($invitation->refresh()->isPending())->toBeTrue();
});

it('never changes an existing role when accepting', function (): void {
    $staff = cohortWithStaff();
    $instructor = $staff['instructor'];
    $invitation = CohortInvitation::factory()->for($staff['cohort'])->create([
        'email' => $instructor->email,
        'role' => CohortRole::Learner,
    ]);

    $this->actingAs($instructor)->post(invitationUrl($invitation, 'invitations.accept'))->assertRedirect(route('dashboard'));

    expect($instructor->cohortRole($staff['cohort']))->toBe(CohortRole::Instructor)
        ->and($instructor->organizationRole($staff['organization']))->toBe(OrganizationRole::Member);
});
