<?php

declare(strict_types=1);

namespace App\Actions\Materials;

use App\Enums\MaterialFormat;
use App\Enums\MaterialStatus;
use App\Jobs\ParseMaterialVersion;
use App\Models\Course;
use App\Models\Material;
use App\Models\MaterialVersion;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Stores an upload as the next version of a material (creating the
 * material when no existing one is given) and queues parsing.
 *
 * The file is kept under its SHA-256, so identical bytes are stored once;
 * an upload whose bytes already exist anywhere in the course is rejected
 * as a duplicate instead of creating a version with the same content.
 * Callers validate size, extension and the MIME type sniffed from the
 * bytes, and authorize, first.
 */
final class UploadMaterialVersion
{
    /**
     * @throws DuplicateMaterialUpload
     */
    public function handle(User $uploader, Course $course, UploadedFile $file, MaterialFormat $format, string $mimeType, ?Material $material, ?string $title = null): MaterialVersion
    {
        if ($material !== null && $material->course_id !== $course->id) {
            throw new RuntimeException('The material belongs to another course.');
        }

        $path = $file->getRealPath();
        $sha256 = $path === false ? false : hash_file('sha256', $path);

        if ($sha256 === false) {
            throw new RuntimeException('The uploaded file could not be read.');
        }

        $this->rejectDuplicate($course, $sha256);

        $storagePath = sprintf('materials/%d/%s.%s', $course->id, $sha256, $format->extension());
        $disk = Storage::disk((string) config('studyloop.materials.disk'));

        if (! $disk->exists($storagePath)) {
            $disk->putFileAs(dirname($storagePath), $file, basename($storagePath));
        }

        try {
            $version = DB::transaction(function () use ($uploader, $course, $file, $format, $mimeType, $material, $title, $sha256, $storagePath): MaterialVersion {
                $material ??= Material::query()->create([
                    'course_id' => $course->id,
                    'title' => trim((string) $title),
                    'created_by' => $uploader->id,
                ]);

                // Serialize version numbering per material.
                Material::query()->whereKey($material->id)->lockForUpdate()->first();

                $next = (int) MaterialVersion::query()->where('material_id', $material->id)->max('version') + 1;

                return MaterialVersion::query()->create([
                    'material_id' => $material->id,
                    'course_id' => $course->id,
                    'version' => $next,
                    'sha256' => $sha256,
                    'format' => $format,
                    'original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 255, 'UTF-8'),
                    'mime_type' => mb_substr($mimeType, 0, 100),
                    'byte_size' => (int) $file->getSize(),
                    'storage_path' => $storagePath,
                    'status' => MaterialStatus::Pending,
                    'uploaded_by' => $uploader->id,
                ]);
            });
        } catch (UniqueConstraintViolationException $exception) {
            // A concurrent upload of the same bytes won the race.
            $this->rejectDuplicate($course, $sha256);

            throw $exception;
        }

        ParseMaterialVersion::dispatch($version->id);

        return $version;
    }

    private function rejectDuplicate(Course $course, string $sha256): void
    {
        $existing = MaterialVersion::query()
            ->with('material')
            ->where('course_id', $course->id)
            ->where('sha256', $sha256)
            ->first();

        if ($existing !== null) {
            throw new DuplicateMaterialUpload($existing);
        }
    }
}
