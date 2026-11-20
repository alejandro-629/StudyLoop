<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;

/**
 * Main navigation for the signed-in user. Each entry is checked against
 * the same policy that guards its page, so the menu never offers a link
 * that would answer 403.
 */
final class Navigation
{
    public function __construct(private readonly CurrentOrganization $currentOrganization) {}

    /**
     * @return list<array{label: string, url: string, active: bool}>
     */
    public function items(User $user): array
    {
        $items = [
            [
                'label' => __('My cohorts'),
                'url' => route('dashboard'),
                'active' => request()->routeIs('dashboard'),
            ],
        ];

        $organization = $this->currentOrganization->for($user);

        if ($organization !== null && $user->can('manage', $organization)) {
            $items[] = [
                'label' => __('Organization'),
                'url' => route('organizations.show', $organization),
                'active' => request()->routeIs('organizations.*'),
            ];
        }

        return $items;
    }

    /**
     * The user's role in the current organization, for the header badge.
     */
    public function roleLabel(User $user): ?string
    {
        $organization = $this->currentOrganization->for($user);

        return $organization === null ? null : $user->organizationRole($organization)?->label();
    }
}
