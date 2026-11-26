<?php

declare(strict_types=1);

namespace App\Support\Materials;

/**
 * Splits a section's text into plain and highlighted segments for a range
 * given in absolute offsets of the version's normalized text. Segments
 * are plain strings; the view escapes them.
 */
final class HighlightedText
{
    /**
     * @param  array{start: int, end: int}|null  $range  absolute, end exclusive
     * @return list<array{text: string, highlighted: bool}>
     */
    public static function segments(string $text, int $offset, ?array $range): array
    {
        $length = mb_strlen($text, 'UTF-8');

        if ($range === null) {
            return [['text' => $text, 'highlighted' => false]];
        }

        $from = max(0, $range['start'] - $offset);
        $to = min($length, $range['end'] - $offset);

        if ($from >= $to) {
            return [['text' => $text, 'highlighted' => false]];
        }

        $segments = [
            ['text' => mb_substr($text, 0, $from, 'UTF-8'), 'highlighted' => false],
            ['text' => mb_substr($text, $from, $to - $from, 'UTF-8'), 'highlighted' => true],
            ['text' => mb_substr($text, $to, null, 'UTF-8'), 'highlighted' => false],
        ];

        return array_values(array_filter($segments, fn (array $segment): bool => $segment['text'] !== ''));
    }
}
