<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * The organization a signed-in user is working in. The choice lives in the
 * session, but it is only ever honoured if the user still belongs to that
 * organization; otherwise the first organization (by name) is used. Scoped
 * to the request, so it is resolved once per request.
 */
final class CurrentOrganization
{
    public const SESSION_KEY = 'current_organization_id';

    /**
     * @var array<int, Collection<int, Organization>>
     */
    private array $organizations = [];

    /**
     * @var array<int, Organization|null>
     */
    private array $current = [];

    /**
     * Organizations the user belongs to, by name.
     *
     * @return Collection<int, Organization>
     */
    public function available(User $user): Collection
    {
        return $this->organizations[$user->id] ??= $user->organizations()
            ->orderBy('organizations.name')
            ->get();
    }

    public function for(User $user): ?Organization
    {
        if (array_key_exists($user->id, $this->current)) {
            return $this->current[$user->id];
        }

        $organizations = $this->available($user);
        $selected = session()->get(self::SESSION_KEY);

        $organization = $organizations->first(
            fn (Organization $organization): bool => $organization->id === (is_numeric($selected) ? (int) $selected : null),
        ) ?? $organizations->first();

        if ($organization === null) {
            session()->forget(self::SESSION_KEY);
        } elseif ($organization->id !== $selected) {
            session()->put(self::SESSION_KEY, $organization->id);
        }

        return $this->current[$user->id] = $organization;
    }

    /**
     * Make $organization the current one. Refuses (403) organizations the
     * user does not belong to, whatever id the browser sent.
     */
    public function switchTo(User $user, Organization $organization): void
    {
        Gate::forUser($user)->authorize('view', $organization);

        session()->put(self::SESSION_KEY, $organization->id);

        unset($this->organizations[$user->id]);
        $this->current[$user->id] = $organization;
    }
}
