<?php

declare(strict_types=1);

namespace App\Support\Materials;

/**
 * Normalized text of one material version and the sections cut from it.
 */
final readonly class ParsedMaterial
{
    /**
     * @param  list<SectionDraft>  $sections
     */
    public function __construct(
        public string $text,
        public array $sections,
    ) {}

    public function characterCount(): int
    {
        return mb_strlen($this->text, 'UTF-8');
    }

    public function textHash(): string
    {
        return TextHash::of($this->text);
    }
}
