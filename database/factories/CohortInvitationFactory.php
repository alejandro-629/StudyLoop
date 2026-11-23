<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CohortRole;
use App\Models\Cohort;
use App\Models\CohortInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CohortInvitation>
 */
final class CohortInvitationFactory extends Factory
{
    protected $model = CohortInvitation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cohort_id' => Cohort::factory(),
            'email' => fake()->unique()->safeEmail(),
            'role' => CohortRole::Learner,
            'invited_by' => User::factory(),
            'expires_at' => now()->addDays(7),
        ];
    }

    public function expired(): self
    {
        return $this->state(fn (array $attributes): array => [
            'expires_at' => now()->subMinute(),
        ]);
    }

    public function revoked(): self
    {
        return $this->afterCreating(function (CohortInvitation $invitation): void {
            $invitation->forceFill(['revoked_at' => now()])->save();
        });
    }
}
