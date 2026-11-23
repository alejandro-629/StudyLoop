<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\CohortInvitation;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The role matrix from docs/security/role-matrix.md, checked over HTTP.
 * "outsider" is an org admin of a different organization.
 *
 * @return array{0: User, 1: array<string, mixed>}
 */
function actorFor(string $who): array
{
    $staff = cohortWithStaff();

    if ($who === 'outsider') {
        $other = Organization::factory()->create();
        $outsider = User::factory()->create();
        $other->members()->attach($outsider, ['role' => OrganizationRole::Admin->value]);

        return [$outsider, $staff];
    }

    return [$staff[$who], $staff];
}

it('enforces the role matrix on organization and cohort pages', function (string $who, int $organizationPage, int $invitationsPage): void {
    [$user, $staff] = actorFor($who);

    $this->actingAs($user)
        ->get(route('organizations.show', $staff['organization']))
        ->assertStatus($organizationPage);

    $this->actingAs($user)
        ->get(route('cohorts.invitations.index', $staff['cohort']))
        ->assertStatus($invitationsPage);
})->with([
    'org admin' => ['admin', 200, 200],
    'instructor' => ['instructor', 403, 200],
    'teaching assistant' => ['ta', 403, 403],
    'learner' => ['learner', 403, 403],
    'admin of another organization' => ['outsider', 403, 403],
]);

it('only offers navigation the role can open', function (string $who, bool $seesOrganization, string $badge): void {
    [$user, $staff] = actorFor($who);

    $response = $this->actingAs($user)->get(route('dashboard'))->assertOk();

    $response->assertSee('aria-current="page"', escape: false)
        ->assertSee($badge);

    $organizationUrl = route('organizations.show', $staff['organization']);
    $seesOrganization
        ? $response->assertSee($organizationUrl)
        : $response->assertDontSee($organizationUrl);
})->with([
    'org admin' => ['admin', true, 'Org admin'],
    'instructor' => ['instructor', false, 'Member'],
    'teaching assistant' => ['ta', false, 'Member'],
    'learner' => ['learner', false, 'Member'],
    'admin of another organization' => ['outsider', false, 'Org admin'],
]);

it('sends guests to sign in', function (): void {
    ['organization' => $organization, 'cohort' => $cohort] = cohortWithStaff();

    $this->get(route('organizations.show', $organization))->assertRedirect(route('login'));
    $this->get(route('cohorts.invitations.index', $cohort))->assertRedirect(route('login'));
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('shows org admins their own courses and cohorts only', function (): void {
    ['admin' => $admin, 'organization' => $organization, 'course' => $course, 'cohort' => $cohort] = cohortWithStaff();
    $other = cohortWithStaff();

    $this->actingAs($admin)
        ->get(route('organizations.show', $organization))
        ->assertOk()
        ->assertSee($course->code)
        ->assertSee($cohort->name)
        ->assertSee('3 members')
        ->assertSee(route('cohorts.invitations.index', $cohort))
        ->assertDontSee($other['cohort']->name)
        ->assertDontSee(route('cohorts.invitations.index', $other['cohort']));
});

it('does not let an admin of another organization withdraw invitations', function (): void {
    [$outsider, $staff] = actorFor('outsider');
    $invitation = CohortInvitation::factory()->for($staff['cohort'])->create([
        'invited_by' => $staff['instructor']->id,
    ]);

    $this->actingAs($outsider)
        ->delete(route('cohorts.invitations.destroy', [$staff['cohort'], $invitation]))
        ->assertForbidden();

    expect($invitation->fresh()?->isPending())->toBeTrue();
});

it('does not let a TA escalate by posting a role the form does not offer', function (): void {
    ['ta' => $ta, 'cohort' => $cohort] = cohortWithStaff();

    $this->actingAs($ta)
        ->post(route('cohorts.invitations.store', $cohort), ['email' => 'friend@example.com', 'role' => 'instructor'])
        ->assertForbidden();

    expect(CohortInvitation::query()->count())->toBe(0);
});
