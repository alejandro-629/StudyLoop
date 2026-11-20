<?php

declare(strict_types=1);

namespace StudyLoop\Scheduler;

use InvalidArgumentException;

/**
 * Thrown when a caller passes a value the scheduler cannot work with,
 * such as a grade outside 0–5 or a malformed study day.
 */
final class InvalidReviewInput extends InvalidArgumentException {}
