<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Organization;
use App\Models\User;
use App\Support\CurrentOrganization;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Header control for people who belong to more than one organization.
 * Everything below the header (cohorts, navigation) follows the choice.
 */
final class OrganizationSwitcher extends Component
{
    public function switchTo(int|string $organizationId): void
    {
        $organization = is_numeric($organizationId)
            ? Organization::query()->find((int) $organizationId)
            : null;

        // Unknown and foreign organizations look the same: 403.
        abort_if($organization === null, 403);

        app(CurrentOrganization::class)->switchTo($this->user(), $organization);

        $this->redirectRoute('dashboard');
    }

    public function render(): View
    {
        $current = app(CurrentOrganization::class);
        $user = $this->user();

        return view('livewire.organization-switcher', [
            'organizations' => $current->available($user),
            'current' => $current->for($user),
        ]);
    }

    private function user(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
