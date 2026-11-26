<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\MaterialFailureReason;
use App\Enums\MaterialStatus;
use App\Models\MaterialVersion;
use App\Support\Materials\MaterialParseFailed;
use App\Support\Materials\MaterialParser;
use App\Support\Materials\TextHash;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Parses one uploaded material version into normalized text and source
 * sections. Only pending versions are touched, so a duplicate or late
 * retry cannot rewrite a version that items may already cite.
 */
final class ParseMaterialVersion implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    /**
     * @var list<int>
     */
    public array $backoff = [10, 60];

    public function __construct(public readonly int $materialVersionId) {}

    public function handle(MaterialParser $parser): void
    {
        $version = MaterialVersion::query()->find($this->materialVersionId);

        if ($version === null || $version->status !== MaterialStatus::Pending) {
            return;
        }

        $bytes = Storage::disk($this->disk())->get($version->storage_path);

        if ($bytes === null || ! hash_equals($version->sha256, hash('sha256', $bytes))) {
            $this->markFailed($version, MaterialFailureReason::FileMissing);

            return;
        }

        try {
            $parsed = $parser->parse($bytes, $version->format);
        } catch (MaterialParseFailed $failure) {
            $this->markFailed($version, $failure->reason);

            return;
        }

        DB::transaction(function () use ($version, $parsed): void {
            $locked = MaterialVersion::query()->lockForUpdate()->find($version->id);

            if ($locked === null || $locked->status !== MaterialStatus::Pending) {
                return;
            }

            $now = now();
            $rows = [];

            foreach ($parsed->sections as $position => $section) {
                $rows[] = [
                    'material_version_id' => $locked->id,
                    'position' => $position + 1,
                    'heading' => $section->heading,
                    'level' => $section->level,
                    'start' => $section->start,
                    'end' => $section->end,
                    'text_hash' => $section->textHash,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('source_sections')->insert($chunk);
            }

            $locked->forceFill([
                'status' => MaterialStatus::Ready,
                'failure_reason' => null,
                'normalized_text' => $parsed->text,
                'text_sha256' => TextHash::of($parsed->text),
                'character_count' => $parsed->characterCount(),
                'parsed_at' => $now,
            ])->save();
        });
    }

    /**
     * Retries are exhausted on an unexpected error (storage outage, bug).
     */
    public function failed(?Throwable $exception): void
    {
        $version = MaterialVersion::query()->find($this->materialVersionId);

        if ($version !== null && $version->status === MaterialStatus::Pending) {
            $this->markFailed($version, MaterialFailureReason::ParserError);
        }
    }

    private function markFailed(MaterialVersion $version, MaterialFailureReason $reason): void
    {
        $version->forceFill([
            'status' => MaterialStatus::Failed,
            'failure_reason' => $reason,
            'parsed_at' => now(),
        ])->save();

        // Ids and the reason only: material text never goes to the log.
        Log::info('Material version could not be parsed.', [
            'material_version_id' => $version->id,
            'reason' => $reason->value,
        ]);
    }

    private function disk(): string
    {
        return (string) config('studyloop.materials.disk');
    }
}
