<?php

declare(strict_types=1);

namespace App\Actions\Materials;

use App\Models\MaterialVersion;
use RuntimeException;

/**
 * The uploaded bytes are identical to a version already stored in this
 * course, so no new version is created.
 */
final class DuplicateMaterialUpload extends RuntimeException
{
    public function __construct(public readonly MaterialVersion $existing)
    {
        parent::__construct('Identical material is already stored as version '.$existing->version.'.');
    }
}
