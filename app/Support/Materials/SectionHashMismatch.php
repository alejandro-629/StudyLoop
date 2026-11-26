<?php

declare(strict_types=1);

namespace App\Support\Materials;

use App\Models\SourceSection;
use RuntimeException;

/**
 * A section's offsets no longer select the text its hash was taken from.
 * Nothing should quote or cite the section until this is resolved.
 */
final class SectionHashMismatch extends RuntimeException
{
    public static function forSection(SourceSection $section): self
    {
        return new self(sprintf(
            'Source section %d [%d, %d) does not match its stored hash.',
            $section->id,
            $section->start,
            $section->end,
        ));
    }
}
