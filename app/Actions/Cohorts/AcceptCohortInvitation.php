<?php

declare(strict_types=1);

namespace App\Actions\Cohorts;

use App\Enums\OrganizationRole;
use App\Models\Cohort;
use App\Models\CohortInvitation;
use App\Models\Enrollment;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class AcceptCohortInvitation
{
    /**
     * Join the cohort with the invited role. The invitation row is locked so
     * two tabs (or a replayed request) cannot use one link twice. An
     * existing enrollment or org membership is never changed: an invitation
     * cannot be used to raise or lower someone's role.
     */
    public function handle(CohortInvitation $invitation, User $user): Enrollment
    {
        return DB::transaction(function () use ($invitation, $user): Enrollment {
            $locked = CohortInvitation::query()->whereKey($invitation->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isPending()) {
                throw InvitationUnavailable::for($locked);
            }

            if (! $locked->isFor($user)) {
                throw InvitationUnavailable::wrongAccount();
            }

            $cohort = Cohort::query()->findOrFail($locked->cohort_id);

            Membership::query()->firstOrCreate(
                ['organization_id' => $cohort->organizationId(), 'user_id' => $user->id],
                ['role' => OrganizationRole::Member],
            );

            $enrollment = Enrollment::query()->firstOrCreate(
                ['cohort_id' => $cohort->id, 'user_id' => $user->id],
                ['role' => $locked->role],
            );

            $locked->forceFill([
                'accepted_at' => now(),
                'accepted_by' => $user->id,
            ])->save();

            return $enrollment;
        });
    }
}
