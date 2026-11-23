<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CohortRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * A user's role in one cohort (cohort_user).
 *
 * @property CohortRole $role
 */
final class Enrollment extends Pivot
{
    public $incrementing = true;

    protected $table = 'cohort_user';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'cohort_id',
        'user_id',
        'role',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => CohortRole::class,
        ];
    }
}
