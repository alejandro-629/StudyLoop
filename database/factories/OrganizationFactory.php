<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Organization>
 */
final class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company().' Academy';

        return [
            'name' => $name,
            'slug' => Str::limit(Str::slug($name), 48, '').'-'.Str::lower(Str::random(6)),
        ];
    }

    public function withMember(User $user, OrganizationRole $role = OrganizationRole::Member): self
    {
        return $this->hasAttached($user, ['role' => $role->value], 'members');
    }
}
