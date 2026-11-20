<?php

declare(strict_types=1);

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the sign-in page for guests', function (): void {
    $this->get(route('login'))
        ->assertOk()
        ->assertSeeLivewire(Login::class)
        ->assertSee('invitation-only');
});

it('signs in with valid credentials and goes to the dashboard', function (): void {
    $user = User::factory()->create(['email' => 'learner@example.com']);

    Livewire::test(Login::class)
        ->set('email', 'Learner@Example.com')
        ->set('password', 'password')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

it('rejects a wrong password without saying which field was wrong', function (): void {
    $user = User::factory()->create();

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest();
});

it('locks the account and address out after five failed attempts', function (): void {
    $user = User::factory()->create();

    foreach (range(1, 5) as $attempt) {
        Livewire::test(Login::class)->set('email', $user->email)->set('password', 'wrong')->call('login');
    }

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest();
});

it('sends signed-in users away from the sign-in page', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('login'))
        ->assertRedirect(route('dashboard'));
});

it('signs out', function (): void {
    $this->actingAs(User::factory()->create())
        ->post(route('logout'))
        ->assertRedirect(route('home'));

    $this->assertGuest();
});

it('requires sign-in for the dashboard', function (): void {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});
