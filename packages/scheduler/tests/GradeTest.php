<?php

declare(strict_types=1);

use StudyLoop\Scheduler\Grade;
use StudyLoop\Scheduler\InvalidReviewInput;

it('treats 3, 4 and 5 as correct and 0, 1 and 2 as misses', function (int $value, bool $correct): void {
    expect(Grade::fromInt($value)->isCorrect())->toBe($correct);
})->with([
    [0, false],
    [1, false],
    [2, false],
    [3, true],
    [4, true],
    [5, true],
]);

it('rejects grades outside 0 to 5', function (int $value): void {
    expect(fn () => Grade::fromInt($value))
        ->toThrow(InvalidReviewInput::class, 'between 0 and 5');
})->with([-1, 6, 100]);

it('keeps the SM-2 numeric values stable', function (): void {
    expect(array_map(fn (Grade $grade): int => $grade->value, Grade::cases()))
        ->toBe([0, 1, 2, 3, 4, 5]);
});
