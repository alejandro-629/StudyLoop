<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\CohortInvitation;
use Illuminate\Support\Facades\URL;

/**
 * Signed invitation links. The signature covers the path and query only
 * (routes use `signed:relative`), so a link keeps working behind a proxy
 * or when the app is reached on another host than APP_URL. It still
 * expires together with the invitation and cannot be edited.
 */
final class InvitationLinks
{
    public static function path(CohortInvitation $invitation, string $route = 'invitations.show'): string
    {
        return URL::temporarySignedRoute(
            $route,
            $invitation->expires_at,
            ['invitation' => $invitation->id],
            absolute: false,
        );
    }

    /**
     * The absolute link that goes into the invitation email.
     */
    public static function url(CohortInvitation $invitation): string
    {
        return url(self::path($invitation));
    }
}
