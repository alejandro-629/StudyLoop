<?php

declare(strict_types=1);

namespace App\Support\Materials;

use App\Enums\MaterialFailureReason;
use App\Enums\MaterialFormat;

/**
 * Raw upload bytes → normalized text → sections. Pure apart from the PDF
 * library: no database, no storage, so it can be tested on fixtures.
 */
final class MaterialParser
{
    public function __construct(
        private readonly TextNormalizer $normalizer,
        private readonly Sectionizer $sectionizer,
        private readonly PdfTextExtractor $pdf,
        private readonly int $maxTextCharacters,
    ) {}

    /**
     * @throws MaterialParseFailed
     */
    public function parse(string $bytes, MaterialFormat $format): ParsedMaterial
    {
        $pages = $format === MaterialFormat::Pdf ? $this->pdf->pages($bytes) : [$bytes];

        $normalized = array_map($this->normalizer->normalize(...), $pages);

        $total = array_sum(array_map(fn (string $page): int => mb_strlen($page, 'UTF-8'), $normalized));

        if (trim(implode('', $normalized)) === '') {
            throw new MaterialParseFailed(MaterialFailureReason::NoText);
        }

        if ($total > $this->maxTextCharacters) {
            throw new MaterialParseFailed(MaterialFailureReason::TooLong);
        }

        return $this->sectionizer->split($format, $normalized);
    }
}
