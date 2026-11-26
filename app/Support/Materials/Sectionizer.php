<?php

declare(strict_types=1);

namespace App\Support\Materials;

use App\Enums\MaterialFormat;

/**
 * Cuts normalized text into sections with code-point offsets.
 *
 *  - Markdown: every ATX heading (`#` to `######`) outside fenced code
 *    starts a section; text before the first heading is a level-0 section
 *    without a heading.
 *  - Plain text: one section without a heading.
 *  - PDF: one section per page that has text, headed "Page N".
 *
 * A section runs from its heading line to the next section and never ends
 * in line breaks. Sections longer than the configured limit are split at
 * the last paragraph break (else line break, else space) before the limit
 * and the parts are headed "<heading> (part N)". Offsets therefore only
 * depend on the normalized text and the limit. Generated headings are not
 * translated, so stored data never depends on the app locale.
 */
final class Sectionizer
{
    private const HEADING_MAX_CHARACTERS = 255;

    public function __construct(private readonly int $maxSectionCharacters = 6000) {}

    /**
     * @param  list<string>  $pages  normalized text, one entry per PDF page (a single entry otherwise)
     */
    public function split(MaterialFormat $format, array $pages): ParsedMaterial
    {
        if ($format === MaterialFormat::Pdf) {
            return $this->splitPages($pages);
        }

        $text = implode("\n\n", array_filter($pages, fn (string $page): bool => $page !== ''));

        $boundaries = $format === MaterialFormat::Markdown
            ? $this->markdownBoundaries($text)
            : [['start' => 0, 'heading' => null, 'level' => 0]];

        return new ParsedMaterial($text, $this->sectionsFrom($text, $boundaries));
    }

    /**
     * @param  list<string>  $pages
     */
    private function splitPages(array $pages): ParsedMaterial
    {
        $text = '';
        $boundaries = [];

        foreach ($pages as $index => $page) {
            if ($page === '') {
                continue;
            }

            if ($text !== '') {
                $text .= "\n\n";
            }

            $boundaries[] = [
                'start' => mb_strlen($text, 'UTF-8'),
                'heading' => sprintf('Page %d', $index + 1),
                'level' => 1,
            ];
            $text .= $page;
        }

        return new ParsedMaterial($text, $this->sectionsFrom($text, $boundaries));
    }

    /**
     * @return list<array{start: int, heading: string|null, level: int}>
     */
    private function markdownBoundaries(string $text): array
    {
        $boundaries = [];
        $offset = 0;
        $fence = null;

        foreach (explode("\n", $text) as $line) {
            if ($fence !== null) {
                if (preg_match('/^ {0,3}(`{3,}|~{3,})[ \t]*$/', $line, $match) === 1
                    && $match[1][0] === $fence[0]
                    && strlen($match[1]) >= strlen($fence)) {
                    $fence = null;
                }
            } elseif (preg_match('/^ {0,3}(`{3,}|~{3,})/', $line, $match) === 1) {
                $fence = $match[1];
            } elseif (preg_match('/^ {0,3}(#{1,6})[ \t]+(.*?)(?:[ \t]+#+)?[ \t]*$/u', $line, $match) === 1
                && trim($match[2]) !== '') {
                $boundaries[] = [
                    'start' => $offset,
                    'heading' => mb_substr(trim($match[2]), 0, self::HEADING_MAX_CHARACTERS, 'UTF-8'),
                    'level' => strlen($match[1]),
                ];
            }

            $offset += mb_strlen($line, 'UTF-8') + 1;
        }

        if ($boundaries === [] || $boundaries[0]['start'] > 0) {
            array_unshift($boundaries, ['start' => 0, 'heading' => null, 'level' => 0]);
        }

        return $boundaries;
    }

    /**
     * @param  list<array{start: int, heading: string|null, level: int}>  $boundaries
     * @return list<SectionDraft>
     */
    private function sectionsFrom(string $text, array $boundaries): array
    {
        $length = mb_strlen($text, 'UTF-8');
        $sections = [];

        foreach ($boundaries as $index => $boundary) {
            $start = $boundary['start'];
            $end = $boundaries[$index + 1]['start'] ?? $length;
            $body = rtrim(mb_substr($text, $start, $end - $start, 'UTF-8'), "\n");

            if (trim($body) === '') {
                continue;
            }

            foreach ($this->splitLong($body) as $part => [$partStart, $partText]) {
                $heading = $boundary['heading'];

                if ($part > 0 && $heading !== null) {
                    $heading = mb_substr(
                        sprintf('%s (part %d)', $heading, $part + 1),
                        0,
                        self::HEADING_MAX_CHARACTERS,
                        'UTF-8',
                    );
                }

                $sections[] = new SectionDraft(
                    heading: $heading,
                    level: $boundary['level'],
                    start: $start + $partStart,
                    end: $start + $partStart + mb_strlen($partText, 'UTF-8'),
                    textHash: TextHash::of($partText),
                );
            }
        }

        return $sections;
    }

    /**
     * @return list<array{0: int, 1: string}> pairs of (offset within $body, part text)
     */
    private function splitLong(string $body): array
    {
        $parts = [];
        $offset = 0;

        while (mb_strlen($body, 'UTF-8') > $this->maxSectionCharacters) {
            $window = mb_substr($body, 0, $this->maxSectionCharacters, 'UTF-8');
            [$cut, $skip] = $this->cutPoint($window);

            $parts[] = [$offset, mb_substr($body, 0, $cut, 'UTF-8')];

            $offset += $cut + $skip;
            $body = mb_substr($body, $cut + $skip, null, 'UTF-8');

            // Paragraph breaks are at most "\n\n" after normalization, but a
            // part must not start with a line break either.
            $leading = mb_strlen($body, 'UTF-8') - mb_strlen(ltrim($body, "\n"), 'UTF-8');
            $offset += $leading;
            $body = ltrim($body, "\n");
        }

        if ($body !== '') {
            $parts[] = [$offset, $body];
        }

        return $parts;
    }

    /**
     * Where to end a part inside the window, and how many separator
     * characters to skip before the next part.
     *
     * @return array{0: int, 1: int}
     */
    private function cutPoint(string $window): array
    {
        foreach (["\n\n", "\n", ' '] as $separator) {
            $position = mb_strrpos($window, $separator, 0, 'UTF-8');

            if ($position !== false && $position > 0) {
                return [$position, mb_strlen($separator, 'UTF-8')];
            }
        }

        return [mb_strlen($window, 'UTF-8'), 0];
    }
}
