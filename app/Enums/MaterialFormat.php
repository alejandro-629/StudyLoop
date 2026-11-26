<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Upload formats StudyLoop accepts. The extension picks the format; the
 * MIME type detected from the file's bytes must agree with it.
 */
enum MaterialFormat: string
{
    case Markdown = 'markdown';
    case Text = 'text';
    case Pdf = 'pdf';

    public static function fromExtension(string $extension): ?self
    {
        return match (mb_strtolower($extension)) {
            'md', 'markdown' => self::Markdown,
            'txt' => self::Text,
            'pdf' => self::Pdf,
            default => null,
        };
    }

    /**
     * @return list<string>
     */
    public static function extensions(): array
    {
        return ['md', 'markdown', 'txt', 'pdf'];
    }

    /**
     * Whether a MIME type detected by libmagic fits this format. libmagic
     * labels Markdown inconsistently (text/plain, text/markdown, sometimes
     * text/x-* for code-heavy notes), so text formats accept any text/*
     * type; the parser still rejects invalid UTF-8.
     */
    public function accepts(string $mimeType): bool
    {
        return match ($this) {
            self::Markdown, self::Text => str_starts_with($mimeType, 'text/'),
            self::Pdf => $mimeType === 'application/pdf',
        };
    }

    public function extension(): string
    {
        return match ($this) {
            self::Markdown => 'md',
            self::Text => 'txt',
            self::Pdf => 'pdf',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Markdown => __('Markdown'),
            self::Text => __('Plain text'),
            self::Pdf => __('PDF'),
        };
    }
}
