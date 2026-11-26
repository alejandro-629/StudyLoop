<?php

declare(strict_types=1);

namespace App\Support\Materials;

/**
 * A section found by the sectionizer, before it is stored.
 */
final readonly class SectionDraft
{
    public function __construct(
        public ?string $heading,
        public int $level,
        public int $start,
        public int $end,
        public string $textHash,
    ) {}
}
