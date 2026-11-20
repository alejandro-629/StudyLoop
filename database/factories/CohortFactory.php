<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CohortRole;
use App\Models\Cohort;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cohort>
 */
final class CohortFactory extends Factory
{
    protected $model = Cohort::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsOn = fake()->dateTimeBetween('-1 month', '+1 month');

        return [
            'course_id' => Course::factory(),
            'name' => 'Section '.fake()->unique()->bothify('?##'),
            'starts_on' => $startsOn,
            'ends_on' => (clone $startsOn)->modify('+15 weeks'),
        ];
    }

    public function withMember(User $user, CohortRole $role): self
    {
        return $this->hasAttached($user, ['role' => $role->value], 'members');
    }
}
