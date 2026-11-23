<?php

declare(strict_types=1);

namespace StudyLoop\Scheduler;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

/**
 * A learner's calendar day for scheduling purposes.
 *
 * A study day starts at 04:00 local time by default, so a session at 01:30
 * still counts toward the day the learner thinks of as "today". Due dates are
 * stored as study days (Y-m-d) and never depend on the server's timezone.
 */
final readonly class StudyDay
{
    public const DEFAULT_DAY_START_HOUR = 4;

    private const FORMAT = 'Y-m-d';

    private function __construct(public string $date)
    {
        $parsed = DateTimeImmutable::createFromFormat('!'.self::FORMAT, $date, new DateTimeZone('UTC'));

        if ($parsed === false || $parsed->format(self::FORMAT) !== $date) {
            throw new InvalidReviewInput(sprintf('A study day must be a valid Y-m-d date, got "%s".', $date));
        }
    }

    public static function fromDate(string $date): self
    {
        return new self($date);
    }

    /**
     * The study day an instant belongs to for a learner in the given timezone.
     */
    public static function at(
        DateTimeInterface $instant,
        DateTimeZone $timezone,
        int $dayStartsAtHour = self::DEFAULT_DAY_START_HOUR,
    ): self {
        if ($dayStartsAtHour < 0 || $dayStartsAtHour > 23) {
            throw new InvalidReviewInput(sprintf('The day start hour must be between 0 and 23, got %d.', $dayStartsAtHour));
        }

        $local = DateTimeImmutable::createFromInterface($instant)->setTimezone($timezone);
        $calendarDay = new self($local->format(self::FORMAT));

        return (int) $local->format('G') < $dayStartsAtHour
            ? $calendarDay->addDays(-1)
            : $calendarDay;
    }

    public function addDays(int $days): self
    {
        $interval = new DateInterval('P'.abs($days).'D');
        $midnight = $this->midnight();
        $moved = $days >= 0 ? $midnight->add($interval) : $midnight->sub($interval);

        return new self($moved->format(self::FORMAT));
    }

    /**
     * Whole days from this day to the other one; negative when the other day is earlier.
     */
    public function daysUntil(self $other): int
    {
        $difference = $this->midnight()->diff($other->midnight());
        $days = (int) $difference->days;

        return $difference->invert === 1 ? -$days : $days;
    }

    public function equals(self $other): bool
    {
        return $this->date === $other->date;
    }

    public function isBefore(self $other): bool
    {
        return $this->date < $other->date;
    }

    public function isAfter(self $other): bool
    {
        return $this->date > $other->date;
    }

    public function toString(): string
    {
        return $this->date;
    }

    private function midnight(): DateTimeImmutable
    {
        return new DateTimeImmutable($this->date.' 00:00:00', new DateTimeZone('UTC'));
    }
}
