<?php

declare(strict_types=1);

use StudyLoop\Scheduler\InvalidReviewInput;
use StudyLoop\Scheduler\StudyDay;

function studyDayAt(string $utcInstant, string $timezone, int $dayStartsAtHour = StudyDay::DEFAULT_DAY_START_HOUR): string
{
    return StudyDay::at(
        new DateTimeImmutable($utcInstant, new DateTimeZone('UTC')),
        new DateTimeZone($timezone),
        $dayStartsAtHour,
    )->toString();
}

describe('day boundary', function (): void {
    it('starts a new study day at 04:00 local time', function (string $utcInstant, string $timezone, string $expected): void {
        expect(studyDayAt($utcInstant, $timezone))->toBe($expected);
    })->with([
        'Bogotá 03:59 belongs to the previous day' => ['2026-11-20 08:59:59', 'America/Bogota', '2026-11-19'],
        'Bogotá 04:00 opens the new day' => ['2026-11-20 09:00:00', 'America/Bogota', '2026-11-20'],
        'Bogotá 01:30 late-night session' => ['2026-11-21 06:30:00', 'America/Bogota', '2026-11-20'],
        'Tokyo is already on the next calendar day' => ['2026-11-20 02:30:00', 'Asia/Tokyo', '2026-11-20'],
        'Bogotá is still on the previous evening' => ['2026-11-20 02:30:00', 'America/Bogota', '2026-11-19'],
        'New Year 03:00 counts toward 31 December' => ['2027-01-01 08:00:00', 'America/New_York', '2026-12-31'],
        'Madrid night after the October DST change' => ['2026-10-25 02:30:00', 'Europe/Madrid', '2026-10-24'],
    ]);

    it('uses plain calendar days when the day starts at midnight', function (): void {
        expect(studyDayAt('2026-11-21 06:30:00', 'America/Bogota', 0))->toBe('2026-11-21');
    });

    it('does not depend on the timezone of the instant it is given', function (): void {
        $fromUtc = StudyDay::at(new DateTimeImmutable('2026-11-21 06:30:00', new DateTimeZone('UTC')), new DateTimeZone('America/Bogota'));
        $fromTokyo = StudyDay::at(new DateTimeImmutable('2026-11-21 15:30:00', new DateTimeZone('Asia/Tokyo')), new DateTimeZone('America/Bogota'));

        expect($fromUtc->equals($fromTokyo))->toBeTrue();
    });

    it('rejects a day start hour outside 0 to 23', function (int $hour): void {
        expect(fn () => studyDayAt('2026-11-20 12:00:00', 'UTC', $hour))
            ->toThrow(InvalidReviewInput::class, 'between 0 and 23');
    })->with([-1, 24]);
});

describe('dates', function (): void {
    it('rejects anything that is not a real Y-m-d date', function (string $date): void {
        expect(fn () => StudyDay::fromDate($date))->toThrow(InvalidReviewInput::class);
    })->with(['2026-02-30', '2026-13-01', '20261120', '2026-11-20 ', '2026-11-20T00:00:00', '']);

    it('moves forward and backward across months and leap days', function (string $from, int $days, string $expected): void {
        expect(StudyDay::fromDate($from)->addDays($days)->toString())->toBe($expected);
    })->with([
        ['2026-11-20', 1, '2026-11-21'],
        ['2026-11-20', 6, '2026-11-26'],
        ['2026-11-30', 1, '2026-12-01'],
        ['2028-02-28', 1, '2028-02-29'],
        ['2028-03-01', -1, '2028-02-29'],
        ['2027-01-01', -1, '2026-12-31'],
        ['2026-11-20', 0, '2026-11-20'],
    ]);

    it('counts whole days between study days in both directions', function (): void {
        $today = StudyDay::fromDate('2026-11-20');
        $due = StudyDay::fromDate('2026-11-26');

        expect($today->daysUntil($due))->toBe(6)
            ->and($due->daysUntil($today))->toBe(-6)
            ->and($today->daysUntil($today))->toBe(0)
            ->and(StudyDay::fromDate('2026-03-01')->daysUntil(StudyDay::fromDate('2026-04-01')))->toBe(31);
    });

    it('orders study days', function (): void {
        $earlier = StudyDay::fromDate('2026-11-19');
        $later = StudyDay::fromDate('2026-11-20');

        expect($earlier->isBefore($later))->toBeTrue()
            ->and($later->isAfter($earlier))->toBeTrue()
            ->and($later->isBefore($earlier))->toBeFalse()
            ->and($later->equals(StudyDay::fromDate('2026-11-20')))->toBeTrue();
    });
});
