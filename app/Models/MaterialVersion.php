<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MaterialFailureReason;
use App\Enums\MaterialFormat;
use App\Enums\MaterialStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One uploaded revision of a material. The raw file is stored under its
 * SHA-256; parsing fills in the normalized text and its sections. A
 * version never changes after it is parsed.
 *
 * @property int $version
 * @property MaterialFormat $format
 * @property MaterialStatus $status
 * @property MaterialFailureReason|null $failure_reason
 * @property int $byte_size
 * @property string|null $normalized_text
 * @property int|null $character_count
 * @property Carbon|null $parsed_at
 */
final class MaterialVersion extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'material_id',
        'course_id',
        'version',
        'sha256',
        'format',
        'original_name',
        'mime_type',
        'byte_size',
        'storage_path',
        'status',
        'uploaded_by',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'normalized_text',
        'storage_path',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'format' => MaterialFormat::class,
            'status' => MaterialStatus::class,
            'failure_reason' => MaterialFailureReason::class,
            'byte_size' => 'integer',
            'character_count' => 'integer',
            'parsed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Material, $this>
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return HasMany<SourceSection, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(SourceSection::class)->orderBy('position');
    }

    public function isReady(): bool
    {
        return $this->status === MaterialStatus::Ready;
    }
}
