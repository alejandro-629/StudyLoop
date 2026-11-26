<?php

declare(strict_types=1);

namespace App\Support\Materials;

/**
 * The one hash used for material text: lowercase hex SHA-256 of the UTF-8
 * bytes. Section hashes, version text hashes and (later) item span hashes
 * all go through here so they stay comparable.
 */
final class TextHash
{
    public static function of(string $text): string
    {
        return hash('sha256', $text);
    }

    /**
     * The `sha256:<hex>` form used in API payloads.
     */
    public static function prefixed(string $hash): string
    {
        return 'sha256:'.$hash;
    }
}
