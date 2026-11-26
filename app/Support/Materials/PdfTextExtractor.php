<?php

declare(strict_types=1);

namespace App\Support\Materials;

use App\Enums\MaterialFailureReason;
use Smalot\PdfParser\Config;
use Smalot\PdfParser\Parser;
use Throwable;

/**
 * Extracts the text layer of a PDF, one string per page. Images are
 * dropped while parsing; scanned PDFs without a text layer yield empty
 * pages (the caller reports "no text"). Damaged or encrypted files fail
 * with UnreadablePdf instead of surfacing parser internals.
 */
final class PdfTextExtractor
{
    public function __construct(private readonly int $maxPages) {}

    /**
     * @return list<string>
     *
     * @throws MaterialParseFailed
     */
    public function pages(string $bytes): array
    {
        // The header may be preceded by a little junk, but must be there.
        if (! str_contains(substr($bytes, 0, 1024), '%PDF-')) {
            throw new MaterialParseFailed(MaterialFailureReason::UnreadablePdf);
        }

        $config = new Config;
        $config->setRetainImageContent(false);
        $config->setDecodeMemoryLimit(32 * 1024 * 1024);

        try {
            $document = (new Parser([], $config))->parseContent($bytes);
            $pages = $document->getPages();
        } catch (Throwable $exception) {
            throw new MaterialParseFailed(MaterialFailureReason::UnreadablePdf, $exception);
        }

        if (count($pages) > $this->maxPages) {
            throw new MaterialParseFailed(MaterialFailureReason::TooManyPages);
        }

        $texts = [];

        foreach ($pages as $page) {
            try {
                $texts[] = $page->getText();
            } catch (Throwable $exception) {
                throw new MaterialParseFailed(MaterialFailureReason::UnreadablePdf, $exception);
            }
        }

        return $texts;
    }
}
