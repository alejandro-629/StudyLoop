<?php

declare(strict_types=1);

namespace StudyLoop\Scheduler;

/**
 * SM-2 answer quality on the classic 0–5 scale.
 *
 * Grades 0–2 are misses and reset the repetition count; 3–5 are correct
 * answers that grow the interval. The application maps a learner's answer
 * (deterministic grading or a self-check rating) to one of these values.
 */
enum Grade: int
{
    case Blackout = 0;
    case Incorrect = 1;
    case IncorrectButFamiliar = 2;
    case CorrectWithDifficulty = 3;
    case CorrectAfterHesitation = 4;
    case Perfect = 5;

    public const PASSING = 3;

    public static function fromInt(int $value): self
    {
        return self::tryFrom($value)
            ?? throw new InvalidReviewInput(sprintf('A grade must be between 0 and 5, got %d.', $value));
    }

    public function isCorrect(): bool
    {
        return $this->value >= self::PASSING;
    }
}
