<?php

declare(strict_types=1);

namespace App\Enums;

enum CohortRole: string
{
    case Instructor = 'instructor';
    case TeachingAssistant = 'ta';
    case Learner = 'learner';

    public function label(): string
    {
        return match ($this) {
            self::Instructor => __('Instructor'),
            self::TeachingAssistant => __('Teaching assistant'),
            self::Learner => __('Learner'),
        };
    }

    /**
     * Roles an instructor may hand out in their own cohort. Only org admins
     * can make someone an instructor, so instructors cannot escalate others
     * (or a second account of their own) to their level.
     *
     * @return list<self>
     */
    public static function invitableByInstructors(): array
    {
        return [self::TeachingAssistant, self::Learner];
    }
}
