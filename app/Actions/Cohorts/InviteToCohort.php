<?php

declare(strict_types=1);

namespace App\Actions\Cohorts;

use App\Enums\CohortRole;
use App\Models\Cohort;
use App\Models\CohortInvitation;
use App\Models\User;
use App\Notifications\CohortInvitationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

final class InviteToCohort
{
    public function handle(User $inviter, Cohort $cohort, string $email, CohortRole $role): CohortInvitation
    {
        Gate::forUser($inviter)->authorize('invite', [$cohort, $role]);

        $email = mb_strtolower(trim($email));

        if ($cohort->members()->where('users.email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => __('This person is already in the cohort.'),
            ]);
        }

        $invitation = DB::transaction(function () use ($inviter, $cohort, $email, $role): CohortInvitation {
            // Only the newest link for an address stays valid.
            $cohort->invitations()
                ->pending()
                ->where('email', $email)
                ->update(['revoked_at' => now()]);

            return $cohort->invitations()->create([
                'email' => $email,
                'role' => $role,
                'invited_by' => $inviter->id,
                'expires_at' => now()->addDays(config()->integer('studyloop.invitation_ttl_days')),
            ]);
        });

        $cohort->loadMissing('course');

        Notification::route('mail', $email)->notify(new CohortInvitationNotification(
            invitation: $invitation,
            cohortName: $cohort->name,
            courseTitle: $cohort->course->title,
            inviterName: $inviter->name,
        ));

        return $invitation;
    }
}
