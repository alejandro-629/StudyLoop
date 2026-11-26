<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Materials\DuplicateMaterialUpload;
use App\Actions\Materials\UploadMaterialVersion;
use App\Enums\MaterialFormat;
use App\Models\Course;
use App\Models\Material;
use App\Models\MaterialVersion;
use App\Models\SourceSection;
use App\Models\User;
use App\Support\Materials\HighlightedText;
use App\Support\Materials\TextHash;
use Closure;
use finfo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Course material: list and upload (instructors and org admins), and the
 * section viewer (also TAs). Learners never reach these pages.
 */
final class MaterialController extends Controller
{
    public function index(Course $course): View
    {
        Gate::authorize('viewMaterials', $course);

        return view('materials.index', [
            'course' => $course,
            'materials' => $course->materials()
                ->with(['versions' => fn ($query) => $query->orderByDesc('version')])
                ->orderBy('title')
                ->get(),
            'maxUploadKb' => (int) config('studyloop.materials.max_upload_kb'),
            'extensions' => MaterialFormat::extensions(),
        ]);
    }

    public function store(Request $request, Course $course, UploadMaterialVersion $upload): RedirectResponse
    {
        Gate::authorize('manageMaterials', $course);

        $validated = $request->validate([
            'material_id' => ['nullable', 'integer', Rule::exists('materials', 'id')->where('course_id', $course->id)],
            // A new version keeps its material's title.
            'title' => [
                Rule::excludeIf($request->filled('material_id')),
                'required',
                'string',
                'max:200',
                Rule::unique('materials', 'title')->where('course_id', $course->id),
            ],
            'file' => [
                'required',
                'file',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($value instanceof UploadedFile && (int) $value->getSize() === 0) {
                        $fail(__('The file is empty.'));
                    }
                },
                'max:'.(int) config('studyloop.materials.max_upload_kb'),
                'extensions:'.implode(',', MaterialFormat::extensions()),
            ],
        ]);

        /** @var UploadedFile $file */
        $file = $validated['file'];
        [$format, $mimeType] = $this->detectFormat($file);

        $material = isset($validated['material_id'])
            ? Material::query()->whereKey($validated['material_id'])->where('course_id', $course->id)->firstOrFail()
            : null;

        /** @var User $user */
        $user = $request->user();

        try {
            $version = $upload->handle($user, $course, $file, $format, $mimeType, $material, $validated['title'] ?? null);
        } catch (DuplicateMaterialUpload $duplicate) {
            throw ValidationException::withMessages([
                'file' => __('This file is identical to “:title” version :version, which is already stored.', [
                    'title' => $duplicate->existing->material->title,
                    'version' => $duplicate->existing->version,
                ]),
            ]);
        }

        $version->load('material');

        return redirect()
            ->route('materials.versions.show', [$course, $version->material, $version])
            ->with('status', __('Uploaded “:title” version :version.', [
                'title' => $version->material->title,
                'version' => $version->version,
            ]));
    }

    public function show(Request $request, Course $course, Material $material, MaterialVersion $version): View
    {
        Gate::authorize('viewMaterials', $course);

        $text = $version->isReady() ? (string) $version->normalized_text : '';
        $selected = $request->integer('section') ?: null;
        $range = $this->requestedRange($request, $version);

        $sections = $version->isReady() ? $version->sections()->get() : collect();

        return view('materials.show', [
            'course' => $course,
            'material' => $material,
            'version' => $version,
            'versions' => $material->versions()->orderByDesc('version')->get(),
            'range' => $range,
            'sections' => $sections->map(function (SourceSection $section) use ($text, $selected, $range): array {
                $verified = $section->matchesText($text);
                $sectionText = mb_substr($text, $section->start, $section->length(), 'UTF-8');

                return [
                    'model' => $section,
                    'verified' => $verified,
                    'selected' => $selected === $section->position,
                    'segments' => HighlightedText::segments($sectionText, $section->start, $range),
                ];
            }),
        ]);
    }

    public function sections(Course $course, Material $material, MaterialVersion $version): JsonResponse
    {
        Gate::authorize('viewMaterials', $course);

        $text = $version->isReady() ? (string) $version->normalized_text : '';

        return response()->json([
            'material' => ['id' => $material->id, 'title' => $material->title],
            'version' => [
                'number' => $version->version,
                'status' => $version->status->value,
                'failureReason' => $version->failure_reason?->value,
                'format' => $version->format->value,
                'sha256' => TextHash::prefixed($version->sha256),
                'textHash' => $version->text_sha256 === null ? null : TextHash::prefixed($version->text_sha256),
                'characters' => $version->character_count,
            ],
            'sections' => $version->isReady()
                ? $version->sections()->get()->map(fn (SourceSection $section): array => [
                    'id' => $section->id,
                    'position' => $section->position,
                    'heading' => $section->heading,
                    'level' => $section->level,
                    'start' => $section->start,
                    'end' => $section->end,
                    'hash' => TextHash::prefixed($section->text_hash),
                    'verified' => $section->matchesText($text),
                    'text' => mb_substr($text, $section->start, $section->length(), 'UTF-8'),
                ])->all()
                : [],
        ]);
    }

    /**
     * The extension chooses the format; the MIME type libmagic detects
     * from the bytes must agree, so a renamed binary is rejected before
     * anything is stored.
     *
     * @return array{0: MaterialFormat, 1: string} the format and the detected MIME type
     */
    private function detectFormat(UploadedFile $file): array
    {
        $format = MaterialFormat::fromExtension($file->getClientOriginalExtension());
        $mime = $this->sniffMimeType($file);

        if ($format === null || ! $format->accepts($mime)) {
            throw ValidationException::withMessages([
                'file' => __('The file content does not match its extension. Upload Markdown, plain text or PDF.'),
            ]);
        }

        return [$format, $mime];
    }

    /**
     * MIME type from the file's bytes (libmagic), never from the client's
     * Content-Type header or the file name.
     */
    private function sniffMimeType(UploadedFile $file): string
    {
        $path = $file->getRealPath();
        $mime = $path === false ? false : (new finfo(FILEINFO_MIME_TYPE))->file($path);

        return is_string($mime) ? $mime : 'application/octet-stream';
    }

    /**
     * @return array{start: int, end: int}|null
     */
    private function requestedRange(Request $request, MaterialVersion $version): ?array
    {
        if (! $request->filled(['start', 'end']) || ! $version->isReady()) {
            return null;
        }

        $start = $request->integer('start');
        $end = $request->integer('end');

        if ($start < 0 || $end <= $start || $end > (int) $version->character_count) {
            return null;
        }

        return ['start' => $start, 'end' => $end];
    }
}
