<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * A user's role inside an organization. Teaching roles (instructor, TA,
 * learner) are per cohort, see CohortRole.
 */
enum OrganizationRole: string
{
    case Admin = 'admin';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Admin => __('Org admin'),
            self::Member => __('Member'),
        };
    }
}
