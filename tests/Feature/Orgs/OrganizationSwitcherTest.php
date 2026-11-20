<?php

declare(strict_types=1);

use App\Enums\CohortRole;
use App\Enums\OrganizationRole;
use App\Livewire\OrganizationSwitcher;
use App\Models\Organization;
use App\Models\User;
use App\Support\CurrentOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * An instructor in "Alpha" who is also a learner in "Beta", plus an
 * organization they do not belong to.
 *
 * @return array{user: User, alpha: array<string, mixed>, beta: array<string, mixed>, outside: Organization}
 */
function memberOfTwoOrganizations(): array
{
    $alpha = cohortWithStaff();
    $alpha['organization']->update(['name' => 'Alpha Institute']);

    $beta = cohortWithStaff();
    $beta['organization']->update(['name' => 'Beta College']);

    $user = $alpha['instructor'];
    $beta['organization']->members()->attach($user, ['role' => OrganizationRole::Member->value]);
    $beta['cohort']->members()->attach($user, ['role' => CohortRole::Learner->value]);

    $outside = Organization::factory()->create(['name' => 'Gamma Outside']);

    return ['user' => $user, 'alpha' => $alpha, 'beta' => $beta, 'outside' => $outside];
}

it('lists only the user\'s organizations, first by name selected', function (): void {
    ['user' => $user] = memberOfTwoOrganizations();

    Livewire::actingAs($user)
        ->test(OrganizationSwitcher::class)
        ->assertSeeHtml('id="organization-switcher"')
        ->assertSee('Alpha Institute')
        ->assertSee('Beta College')
        ->assertDontSee('Gamma Outside');

    expect(session(CurrentOrganization::SESSION_KEY))->toBe(Organization::query()->where('name', 'Alpha Institute')->value('id'));
});

it('switches organization and scopes the dashboard to it', function (): void {
    ['user' => $user, 'alpha' => $alpha, 'beta' => $beta] = memberOfTwoOrganizations();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSee($alpha['cohort']->name)
        ->assertDontSee($beta['cohort']->name);

    Livewire::actingAs($user)
        ->test(OrganizationSwitcher::class)
        ->call('switchTo', $beta['organization']->id)
        ->assertRedirect(route('dashboard'));

    expect(session(CurrentOrganization::SESSION_KEY))->toBe($beta['organization']->id);

    $this->actingAs($user)
        ->withSession([CurrentOrganization::SESSION_KEY => $beta['organization']->id])
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Beta College')
        ->assertSee($beta['cohort']->name)
        ->assertSee('Learner')
        ->assertDontSee($alpha['cohort']->name)
        ->assertDontSee(route('cohorts.invitations.index', $beta['cohort']));
});

it('refuses to switch to an organization the user does not belong to', function (mixed $target): void {
    ['user' => $user, 'outside' => $outside] = memberOfTwoOrganizations();
    $id = $target === 'outside' ? $outside->id : $target;

    Livewire::actingAs($user)
        ->test(OrganizationSwitcher::class)
        ->call('switchTo', $id)
        ->assertForbidden();

    expect(session(CurrentOrganization::SESSION_KEY))->not->toBe($outside->id);
})->with([
    'another organization' => 'outside',
    'an unknown id' => 999_999,
    'a non-numeric id' => 'abc',
]);

it('ignores a stored organization the user no longer belongs to', function (): void {
    ['user' => $user, 'alpha' => $alpha, 'outside' => $outside] = memberOfTwoOrganizations();
    $outsideCourse = App\Models\Course::factory()->for($outside)->create();
    $outsideCohort = App\Models\Cohort::factory()->for($outsideCourse)->create();

    $this->actingAs($user)
        ->withSession([CurrentOrganization::SESSION_KEY => $outside->id])
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee($alpha['cohort']->name)
        ->assertDontSee($outsideCohort->name)
        ->assertDontSee('Gamma Outside')
        ->assertSessionHas(CurrentOrganization::SESSION_KEY, $alpha['organization']->id);
});

it('shows the organization name instead of a menu for single-organization users', function (): void {
    ['learner' => $learner, 'organization' => $organization] = cohortWithStaff();

    Livewire::actingAs($learner)
        ->test(OrganizationSwitcher::class)
        ->assertDontSeeHtml('<select')
        ->assertSee($organization->name);
});

it('moves to the organization of a cohort just joined', function (): void {
    ['user' => $user, 'beta' => $beta] = memberOfTwoOrganizations();
    $invitation = App\Models\CohortInvitation::factory()->for($beta['cohort'])->create([
        'email' => $user->email,
        'role' => CohortRole::Learner,
        'invited_by' => $beta['admin']->id,
    ]);
    $beta['cohort']->members()->detach($user);

    $this->actingAs($user)
        ->withSession([CurrentOrganization::SESSION_KEY => $user->organizations()->where('organizations.name', 'Alpha Institute')->value('organizations.id')])
        ->post(App\Support\InvitationLinks::path($invitation, 'invitations.accept'))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas(CurrentOrganization::SESSION_KEY, $beta['organization']->id);
});
