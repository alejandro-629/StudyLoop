<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Why a material version could not be parsed. Stored on the version and
 * shown to the instructor; never contains file contents.
 */
enum MaterialFailureReason: string
{
    case InvalidEncoding = 'invalid_encoding';
    case UnreadablePdf = 'unreadable_pdf';
    case TooManyPages = 'too_many_pages';
    case NoText = 'no_text';
    case TooLong = 'too_long';
    case FileMissing = 'file_missing';
    case ParserError = 'parser_error';

    public function label(): string
    {
        return match ($this) {
            self::InvalidEncoding => __('The file is not valid UTF-8 text.'),
            self::UnreadablePdf => __('The PDF could not be read. It may be damaged or password-protected.'),
            self::TooManyPages => __('The PDF has more pages than allowed.'),
            self::NoText => __('No text was found. Scanned PDFs without a text layer are not supported.'),
            self::TooLong => __('The extracted text is longer than allowed.'),
            self::FileMissing => __('The stored file is missing or has changed since upload.'),
            self::ParserError => __('Parsing failed unexpectedly. Try uploading the file again.'),
        };
    }
}
