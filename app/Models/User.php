<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CohortRole;
use App\Enums\OrganizationRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

final class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * @return BelongsToMany<Organization, $this, Membership, 'membership'>
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)
            ->using(Membership::class)
            ->as('membership')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Cohort, $this, Enrollment, 'enrollment'>
     */
    public function cohorts(): BelongsToMany
    {
        return $this->belongsToMany(Cohort::class)
            ->using(Enrollment::class)
            ->as('enrollment')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function organizationRole(Organization|int $organization): ?OrganizationRole
    {
        $organizationId = $organization instanceof Organization ? $organization->id : $organization;

        return Membership::query()
            ->where('organization_id', $organizationId)
            ->where('user_id', $this->id)
            ->first()
            ?->role;
    }

    public function isOrganizationAdmin(Organization|int $organization): bool
    {
        return $this->organizationRole($organization) === OrganizationRole::Admin;
    }

    public function cohortRole(Cohort $cohort): ?CohortRole
    {
        return Enrollment::query()
            ->where('cohort_id', $cohort->id)
            ->where('user_id', $this->id)
            ->first()
            ?->role;
    }
}
