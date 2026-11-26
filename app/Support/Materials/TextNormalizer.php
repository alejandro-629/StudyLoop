<?php

declare(strict_types=1);

namespace App\Support\Materials;

use App\Enums\MaterialFailureReason;
use Normalizer;

/**
 * Turns extracted text into the canonical form that section offsets and
 * hashes refer to. The rules are deliberately small and deterministic:
 * the same input always yields byte-identical output.
 *
 *  1. Strip a leading UTF-8 byte order mark; reject invalid UTF-8.
 *  2. Unicode NFC, so "é" typed two ways hashes the same.
 *  3. CRLF, CR, U+2028 and U+2029 become LF.
 *  4. Drop C0/C1 control characters (except LF and TAB), zero-width
 *     spaces and stray BOMs. Joiners (U+200C/U+200D) are kept.
 *  5. Remove trailing spaces and tabs on every line.
 *  6. Collapse three or more consecutive line breaks to two.
 *  7. Trim line breaks at the start and end.
 */
final class TextNormalizer
{
    /**
     * @throws MaterialParseFailed
     */
    public function normalize(string $text): string
    {
        if (str_starts_with($text, "\xEF\xBB\xBF")) {
            $text = substr($text, 3);
        }

        if (! mb_check_encoding($text, 'UTF-8')) {
            throw new MaterialParseFailed(MaterialFailureReason::InvalidEncoding);
        }

        $normalized = Normalizer::normalize($text, Normalizer::FORM_C);

        if (! is_string($normalized)) {
            throw new MaterialParseFailed(MaterialFailureReason::InvalidEncoding);
        }

        $text = str_replace(["\r\n", "\r", "\u{2028}", "\u{2029}"], "\n", $normalized);
        $text = (string) preg_replace('/[\x{0000}-\x{0008}\x{000B}-\x{001F}\x{007F}-\x{009F}\x{200B}\x{FEFF}]/u', '', $text);
        $text = (string) preg_replace('/[ \t]+$/m', '', $text);
        $text = (string) preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text, "\n");
    }
}
