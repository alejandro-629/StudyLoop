<?php

declare(strict_types=1);

namespace App\Support\Materials;

use App\Enums\MaterialFailureReason;
use RuntimeException;
use Throwable;

/**
 * Expected parsing failure (bad input, not a bug). The version is marked
 * failed with the reason; the job is not retried.
 */
final class MaterialParseFailed extends RuntimeException
{
    public function __construct(public readonly MaterialFailureReason $reason, ?Throwable $previous = null)
    {
        parent::__construct('Material parsing failed: '.$reason->value, 0, $previous);
    }
}
