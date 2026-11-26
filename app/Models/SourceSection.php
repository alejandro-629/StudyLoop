<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Materials\SectionHashMismatch;
use App\Support\Materials\TextHash;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A heading-delimited range of a material version's normalized text.
 * Offsets are Unicode code points (start inclusive, end exclusive);
 * `text_hash` is the SHA-256 of exactly that range, so anything that
 * cites the section can prove it still reads the same words.
 *
 * @property int $position
 * @property string|null $heading
 * @property int $level
 * @property int $start
 * @property int $end
 * @property string $text_hash
 */
final class SourceSection extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'material_version_id',
        'position',
        'heading',
        'level',
        'start',
        'end',
        'text_hash',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'level' => 'integer',
            'start' => 'integer',
            'end' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<MaterialVersion, $this>
     */
    public function materialVersion(): BelongsTo
    {
        return $this->belongsTo(MaterialVersion::class);
    }

    public function length(): int
    {
        return $this->end - $this->start;
    }

    /**
     * The section's text, cut from the given normalized text and checked
     * against the stored hash. Pass the version's text when listing many
     * sections to avoid reloading it per section.
     *
     * @throws SectionHashMismatch when the offsets no longer select the hashed text
     */
    public function verifiedText(string $normalizedText): string
    {
        $text = mb_substr($normalizedText, $this->start, $this->length(), 'UTF-8');

        if (! hash_equals($this->text_hash, TextHash::of($text))) {
            throw SectionHashMismatch::forSection($this);
        }

        return $text;
    }

    public function matchesText(string $normalizedText): bool
    {
        try {
            $this->verifiedText($normalizedText);
        } catch (SectionHashMismatch) {
            return false;
        }

        return true;
    }
}
