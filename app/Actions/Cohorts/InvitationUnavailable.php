<?php

declare(strict_types=1);

namespace App\Actions\Cohorts;

use App\Models\CohortInvitation;
use RuntimeException;

final class InvitationUnavailable extends RuntimeException
{
    private function __construct(string $message, public readonly int $status)
    {
        parent::__construct($message);
    }

    /**
     * The link itself is no longer usable (410 Gone).
     */
    public static function for(CohortInvitation $invitation): self
    {
        return match (true) {
            $invitation->isAccepted() => new self(__('This invitation has already been used.'), 410),
            $invitation->isRevoked() => new self(__('This invitation was withdrawn.'), 410),
            default => new self(__('This invitation has expired. Ask your instructor for a new one.'), 410),
        };
    }

    /**
     * The link is fine but the signed-in account is not the invited one (403).
     */
    public static function wrongAccount(): self
    {
        return new self(__('This invitation was sent to a different email address. Sign out and open the link again.'), 403);
    }
}
