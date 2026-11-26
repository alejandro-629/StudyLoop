<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * Builds small, valid PDFs with a text layer (Helvetica, one text line per
 * entry) so parsing tests need no binary fixtures in the repository.
 */
final class MinimalPdf
{
    /**
     * @param  list<list<string>>  $pages  lines of ASCII text per page; an empty list makes a blank page
     */
    public static function withPages(array $pages): string
    {
        $objects = [];
        $pageCount = count($pages);
        $firstPage = 4;

        $kids = [];
        for ($i = 0; $i < $pageCount; $i++) {
            $kids[] = ($firstPage + 2 * $i).' 0 R';
        }

        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', $kids).'] /Count '.$pageCount.' >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';

        foreach ($pages as $i => $lines) {
            $pageId = $firstPage + 2 * $i;
            $contentId = $pageId + 1;

            $stream = '';
            if ($lines !== []) {
                $stream = "BT\n/F1 12 Tf\n14 TL\n72 720 Td\n";
                foreach ($lines as $line) {
                    $stream .= '('.self::escape($line).") Tj T*\n";
                }
                $stream .= 'ET';
            }

            $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 3 0 R >> >> /Contents '.$contentId.' 0 R >>';
            $objects[$contentId] = '<< /Length '.strlen($stream)." >>\nstream\n".$stream."\nendstream";
        }

        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id." 0 obj\n".$body."\nendobj\n";
        }

        $xref = strlen($pdf);
        $size = count($objects) + 1;
        $pdf .= "xref\n0 ".$size."\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf."trailer\n<< /Size ".$size." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF\n";
    }

    private static function escape(string $text): string
    {
        return strtr($text, ['\\' => '\\\\', '(' => '\\(', ')' => '\\)']);
    }
}
