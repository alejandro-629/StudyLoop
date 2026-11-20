<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\CohortInvitationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\InvitationController;
use App\Livewire\Auth\Login;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('/health', HealthController::class)->name('health');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', Login::class)->name('login');
});

// Invitation links work signed in or out; the signature covers the expiry.
Route::middleware('signed')->group(function (): void {
    Route::get('/invitations/{invitation}', [InvitationController::class, 'show'])
        ->name('invitations.show');
    Route::post('/invitations/{invitation}/accept', [InvitationController::class, 'accept'])
        ->middleware('throttle:10,1')
        ->name('invitations.accept');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', LogoutController::class)->name('logout');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::scopeBindings()->group(function (): void {
        Route::get('/cohorts/{cohort}/invitations', [CohortInvitationController::class, 'index'])
            ->name('cohorts.invitations.index');
        Route::post('/cohorts/{cohort}/invitations', [CohortInvitationController::class, 'store'])
            ->middleware('throttle:30,1')
            ->name('cohorts.invitations.store');
        Route::delete('/cohorts/{cohort}/invitations/{invitation}', [CohortInvitationController::class, 'destroy'])
            ->name('cohorts.invitations.destroy');
    });
});
