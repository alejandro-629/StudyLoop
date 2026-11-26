<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MaterialStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A piece of course material (notes, slides, a reading). Every upload
 * creates a new immutable version; old versions are kept so items keep
 * pointing at the exact text they were written from.
 */
final class Material extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'course_id',
        'title',
        'created_by',
    ];

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return HasMany<MaterialVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(MaterialVersion::class);
    }

    /**
     * @return HasOne<MaterialVersion, $this>
     */
    public function latestVersion(): HasOne
    {
        return $this->hasOne(MaterialVersion::class)->ofMany('version', 'max');
    }

    /**
     * The newest version that parsed successfully.
     *
     * @return HasOne<MaterialVersion, $this>
     */
    public function currentVersion(): HasOne
    {
        return $this->hasOne(MaterialVersion::class)->ofMany(
            ['version' => 'max'],
            fn ($query) => $query->where('status', MaterialStatus::Ready->value),
        );
    }
}
