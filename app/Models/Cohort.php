<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CohortFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Cohort extends Model
{
    /** @use HasFactory<CohortFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'course_id',
        'name',
        'starts_on',
        'ends_on',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return BelongsToMany<User, $this, Enrollment, 'enrollment'>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(Enrollment::class)
            ->as('enrollment')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * @return HasMany<CohortInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(CohortInvitation::class);
    }

    /**
     * The owning organization's id, without lazy-loading the course.
     */
    public function organizationId(): int
    {
        if ($this->relationLoaded('course')) {
            return $this->course->organization_id;
        }

        return (int) Course::query()->whereKey($this->course_id)->value('organization_id');
    }
}
