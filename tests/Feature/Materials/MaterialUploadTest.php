<?php

declare(strict_types=1);

use App\Enums\CohortRole;
use App\Enums\MaterialFailureReason;
use App\Enums\MaterialFormat;
use App\Enums\MaterialStatus;
use App\Enums\OrganizationRole;
use App\Jobs\ParseMaterialVersion;
use App\Models\Cohort;
use App\Models\Course;
use App\Models\Material;
use App\Models\MaterialVersion;
use App\Models\Organization;
use App\Models\SourceSection;
use App\Models\User;
use App\Support\Materials\MaterialParser;
use App\Support\Materials\SectionHashMismatch;
use App\Support\Materials\TextHash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Support\MinimalPdf;

uses(RefreshDatabase::class);

beforeEach(fn () => Storage::fake('local'));

const CACHING_NOTES = "# Intro to HTTP caching\nCaches keep copies of responses.\n\n## Cache-Control\nThe no-store directive forbids storing the response.\n\n## Validators\nAn ETag lets a cache revalidate.\n";

function markdownUpload(string $content = CACHING_NOTES, string $name = 'http-caching.md'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, $content);
}

it('stores an upload as version 1, parses it and records hashed sections', function (): void {
    ['instructor' => $instructor, 'course' => $course] = cohortWithStaff();

    $response = $this->actingAs($instructor)->post(route('materials.store', $course), [
        'title' => 'HTTP caching notes',
        'file' => markdownUpload(),
    ]);

    $version = MaterialVersion::query()->sole();
    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('materials.versions.show', [$course, $version->material, $version]));

    expect($version->version)->toBe(1)
        ->and($version->status)->toBe(MaterialStatus::Ready)
        ->and($version->format)->toBe(MaterialFormat::Markdown)
        ->and($version->sha256)->toBe(hash('sha256', CACHING_NOTES))
        ->and($version->storage_path)->toBe("materials/{$course->id}/{$version->sha256}.md")
        ->and($version->normalized_text)->toBe(rtrim(CACHING_NOTES, "\n"))
        ->and($version->text_sha256)->toBe(TextHash::of(rtrim(CACHING_NOTES, "\n")))
        ->and($version->uploaded_by)->toBe($instructor->id);

    Storage::disk('local')->assertExists($version->storage_path);

    $sections = $version->sections()->get();
    expect($sections->pluck('heading')->all())->toBe(['Intro to HTTP caching', 'Cache-Control', 'Validators'])
        ->and($sections->pluck('position')->all())->toBe([1, 2, 3])
        ->and($sections[1]->verifiedText((string) $version->normalized_text))
        ->toBe("## Cache-Control\nThe no-store directive forbids storing the response.");
});

it('queues parsing instead of doing it in the request', function (): void {
    Queue::fake();
    ['instructor' => $instructor, 'course' => $course] = cohortWithStaff();

    $this->actingAs($instructor)->post(route('materials.store', $course), [
        'title' => 'Queued notes',
        'file' => markdownUpload(),
    ])->assertSessionHasNoErrors();

    $version = MaterialVersion::query()->sole();
    expect($version->status)->toBe(MaterialStatus::Pending);
    Queue::assertPushed(ParseMaterialVersion::class, fn (ParseMaterialVersion $job): bool => $job->materialVersionId === $version->id);

    $this->get(route('materials.versions.show', [$course, $version->material, $version]))
        ->assertOk()
        ->assertSee('This version is being parsed.');
});

it('adds new versions to an existing material and keeps the old ones', function (): void {
    ['instructor' => $instructor, 'course' => $course] = cohortWithStaff();
    $this->actingAs($instructor);

    $this->post(route('materials.store', $course), ['title' => 'Notes', 'file' => markdownUpload()])->assertSessionHasNoErrors();
    $material = Material::query()->sole();

    $this->post(route('materials.store', $course), [
        'material_id' => $material->id,
        'title' => 'Ignored for new versions',
        'file' => markdownUpload(CACHING_NOTES."\n## Freshness\nmax-age sets a lifetime.\n"),
    ])->assertSessionHasNoErrors();

    expect($material->versions()->orderBy('version')->pluck('version')->all())->toBe([1, 2])
        ->and($material->fresh()?->title)->toBe('Notes')
        ->and($material->currentVersion()->first()?->version)->toBe(2)
        ->and(MaterialVersion::query()->where('version', 2)->sole()->sections()->count())->toBe(4);
});

it('rejects a byte-identical upload anywhere in the course', function (): void {
    ['instructor' => $instructor, 'course' => $course] = cohortWithStaff();
    $this->actingAs($instructor);

    $this->post(route('materials.store', $course), ['title' => 'Notes', 'file' => markdownUpload()])->assertSessionHasNoErrors();
    $material = Material::query()->sole();

    $this->post(route('materials.store', $course), ['material_id' => $material->id, 'file' => markdownUpload(name: 'renamed.md')])
        ->assertSessionHasErrors(['file' => 'This file is identical to “Notes” version 1, which is already stored.']);
    $this->post(route('materials.store', $course), ['title' => 'Copy', 'file' => markdownUpload(name: 'copy.md')])
        ->assertSessionHasErrors('file');

    expect(MaterialVersion::query()->count())->toBe(1)
        ->and(Material::query()->count())->toBe(1);
});

it('accepts the same file in a different course', function (): void {
    ['admin' => $admin, 'course' => $course, 'organization' => $organization] = cohortWithStaff();
    $otherCourse = Course::factory()->for($organization)->create();
    $this->actingAs($admin);

    $this->post(route('materials.store', $course), ['title' => 'Notes', 'file' => markdownUpload()])->assertSessionHasNoErrors();
    $this->post(route('materials.store', $otherCourse), ['title' => 'Notes', 'file' => markdownUpload()])->assertSessionHasNoErrors();

    expect(MaterialVersion::query()->count())->toBe(2);
});

it('requires a unique title per course for new materials', function (): void {
    ['instructor' => $instructor, 'course' => $course] = cohortWithStaff();
    $this->actingAs($instructor);

    $this->post(route('materials.store', $course), ['title' => 'Notes', 'file' => markdownUpload()])->assertSessionHasNoErrors();
    $this->post(route('materials.store', $course), ['title' => 'Notes', 'file' => markdownUpload("# Other\ntext")])
        ->assertSessionHasErrors('title');
    $this->post(route('materials.store', $course), ['file' => markdownUpload("# Other\ntext")])
        ->assertSessionHasErrors('title');
});

it('rejects files that are too large, empty, of an unsupported type or disguised', function (array $payload, string $error): void {
    config(['studyloop.materials.max_upload_kb' => 1]);
    ['instructor' => $instructor, 'course' => $course] = cohortWithStaff();

    $this->actingAs($instructor)
        ->post(route('materials.store', $course), ['title' => 'Bad', ...$payload])
        ->assertSessionHasErrors($error);

    expect(MaterialVersion::query()->count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
})->with([
    'too large' => [fn (): array => ['file' => UploadedFile::fake()->createWithContent('big.md', str_repeat('a', 2048))], 'file'],
    'empty' => [fn (): array => ['file' => UploadedFile::fake()->createWithContent('empty.md', '')], 'file'],
    'unsupported extension' => [fn (): array => ['file' => UploadedFile::fake()->createWithContent('notes.docx', 'text')], 'file'],
    'text named .pdf' => [fn (): array => ['file' => UploadedFile::fake()->createWithContent('notes.pdf', 'just text')], 'file'],
    'pdf named .md' => [fn (): array => ['file' => UploadedFile::fake()->createWithContent('notes.md', MinimalPdf::withPages([['hi']]))], 'file'],
    'binary named .txt' => [fn (): array => ['file' => UploadedFile::fake()->createWithContent('notes.txt', "\x7FELF\x02\x01\x01\x00".str_repeat("\x00", 64))], 'file'],
    'missing file' => [fn (): array => [], 'file'],
    'material of another course' => [fn (): array => [
        'material_id' => Material::query()->create(['course_id' => Course::factory()->create()->id, 'title' => 'Elsewhere'])->id,
        'file' => markdownUpload(),
    ], 'material_id'],
]);

it('extracts PDF text page by page', function (): void {
    ['instructor' => $instructor, 'course' => $course] = cohortWithStaff();
    $pdf = MinimalPdf::withPages([
        ['HTTP caching basics', 'Caches keep copies of responses.'],
        [],
        ['No-store (never cache)'],
    ]);

    $this->actingAs($instructor)->post(route('materials.store', $course), [
        'title' => 'Slides',
        'file' => UploadedFile::fake()->createWithContent('slides.pdf', $pdf),
    ])->assertSessionHasNoErrors();

    $version = MaterialVersion::query()->sole();
    expect($version->status)->toBe(MaterialStatus::Ready)
        ->and($version->format)->toBe(MaterialFormat::Pdf)
        ->and($version->mime_type)->toBe('application/pdf');

    $sections = $version->sections()->get();
    expect($sections->pluck('heading')->all())->toBe(['Page 1', 'Page 3'])
        ->and($sections[0]->verifiedText((string) $version->normalized_text))->toContain('Caches keep copies of responses.')
        ->and($sections[1]->verifiedText((string) $version->normalized_text))->toContain('No-store (never cache)');
});

it('marks unreadable input as failed with a reason', function (string $name, string $content, MaterialFailureReason $reason): void {
    ['instructor' => $instructor, 'course' => $course] = cohortWithStaff();

    $this->actingAs($instructor)->post(route('materials.store', $course), [
        'title' => 'Broken',
        'file' => UploadedFile::fake()->createWithContent($name, $content),
    ])->assertSessionHasNoErrors();

    $version = MaterialVersion::query()->sole();
    expect($version->status)->toBe(MaterialStatus::Failed)
        ->and($version->failure_reason)->toBe($reason)
        ->and($version->normalized_text)->toBeNull()
        ->and($version->sections()->count())->toBe(0);

    $this->get(route('materials.versions.show', [$course, $version->material, $version]))
        ->assertOk()
        ->assertSee($reason->label());
})->with([
    'invalid UTF-8' => ['latin1.txt', "Caf\xE9 cr\xE8me br\xFBl\xE9e is a dessert and this text is long enough.", MaterialFailureReason::InvalidEncoding],
    'truncated PDF' => ['broken.pdf', substr(MinimalPdf::withPages([['text']]), 0, 60), MaterialFailureReason::UnreadablePdf],
    'PDF without text' => ['scan.pdf', MinimalPdf::withPages([[], []]), MaterialFailureReason::NoText],
    'only whitespace' => ['blank.md', "   \n\n\t\n", MaterialFailureReason::NoText],
]);

it('enforces the page and text length limits after extraction', function (): void {
    ['instructor' => $instructor, 'course' => $course] = cohortWithStaff();
    $this->actingAs($instructor);

    config(['studyloop.materials.max_pdf_pages' => 1]);
    $this->post(route('materials.store', $course), [
        'title' => 'Two pages',
        'file' => UploadedFile::fake()->createWithContent('two.pdf', MinimalPdf::withPages([['one'], ['two']])),
    ]);

    config(['studyloop.materials.max_text_characters' => 10]);
    $this->post(route('materials.store', $course), [
        'title' => 'Too long',
        'file' => markdownUpload('# Eleven+ characters'),
    ]);

    expect(MaterialVersion::query()->orderBy('id')->pluck('failure_reason')->all())
        ->toBe([MaterialFailureReason::TooManyPages, MaterialFailureReason::TooLong]);
});

it('never rewrites a parsed version when the job runs again', function (): void {
    ['instructor' => $instructor, 'course' => $course] = cohortWithStaff();
    $this->actingAs($instructor)->post(route('materials.store', $course), ['title' => 'Notes', 'file' => markdownUpload()]);
    $version = MaterialVersion::query()->sole();
    $parsedAt = $version->parsed_at;

    (new ParseMaterialVersion($version->id))->handle(app(MaterialParser::class));

    expect($version->sections()->count())->toBe(3)
        ->and($version->fresh()?->parsed_at?->equalTo($parsedAt))->toBeTrue();
});

it('fails a version whose stored file changed after upload', function (): void {
    Queue::fake();
    ['instructor' => $instructor, 'course' => $course] = cohortWithStaff();
    $this->actingAs($instructor)->post(route('materials.store', $course), ['title' => 'Notes', 'file' => markdownUpload()]);
    $version = MaterialVersion::query()->sole();

    Storage::disk('local')->put($version->storage_path, 'tampered');
    (new ParseMaterialVersion($version->id))->handle(app(MaterialParser::class));

    expect($version->fresh()?->failure_reason)->toBe(MaterialFailureReason::FileMissing);
});

it('detects offset drift through the section hash', function (): void {
    ['instructor' => $instructor, 'course' => $course] = cohortWithStaff();
    $this->actingAs($instructor)->post(route('materials.store', $course), ['title' => 'Notes', 'file' => markdownUpload()]);
    $version = MaterialVersion::query()->sole();
    $section = $version->sections()->where('position', 2)->sole();
    $text = (string) $version->normalized_text;

    expect($section->matchesText($text))->toBeTrue();

    $shifted = 'X'.$text;
    expect($section->matchesText($shifted))->toBeFalse();
    $section->verifiedText($shifted);
})->throws(SectionHashMismatch::class);

it('flags a drifted section in the viewer instead of trusting it', function (): void {
    ['instructor' => $instructor, 'course' => $course] = cohortWithStaff();
    $this->actingAs($instructor)->post(route('materials.store', $course), ['title' => 'Notes', 'file' => markdownUpload()]);
    $version = MaterialVersion::query()->sole();
    SourceSection::query()->where('position', 2)->update(['start' => 1]);

    $this->get(route('materials.versions.show', [$course, $version->material, $version]))
        ->assertOk()
        ->assertSee('hash mismatch');

    $json = $this->getJson(route('materials.versions.sections', [$course, $version->material, $version]))->json('sections');
    expect(array_column($json, 'verified'))->toBe([true, false, true]);
});

it('shows sections with offsets and highlights a requested range', function (): void {
    ['instructor' => $instructor, 'course' => $course] = cohortWithStaff();
    $this->actingAs($instructor)->post(route('materials.store', $course), ['title' => 'Notes', 'file' => markdownUpload()]);
    $version = MaterialVersion::query()->sole();
    $text = (string) $version->normalized_text;
    $start = mb_strpos($text, 'no-store');

    $this->get(route('materials.versions.show', [$course, $version->material, $version, 'section' => 2, 'start' => $start, 'end' => $start + 8]))
        ->assertOk()
        ->assertSee('Cache-Control')
        ->assertSee('hash verified')
        ->assertSee('<mark class="bg-amber-200">no-store</mark>', false)
        ->assertSee('aria-current="true"', false);

    // Out-of-range highlights are ignored rather than failing the page.
    $this->get(route('materials.versions.show', [$course, $version->material, $version, 'start' => 5, 'end' => 100000]))
        ->assertOk()
        ->assertDontSee('<mark', false);
});

it('returns sections with offsets and prefixed hashes as JSON', function (): void {
    ['ta' => $ta, 'instructor' => $instructor, 'course' => $course] = cohortWithStaff();
    $this->actingAs($instructor)->post(route('materials.store', $course), ['title' => 'Notes', 'file' => markdownUpload()]);
    $version = MaterialVersion::query()->sole();

    $this->actingAs($ta)
        ->getJson(route('materials.versions.sections', [$course, $version->material, $version]))
        ->assertOk()
        ->assertJsonPath('version.number', 1)
        ->assertJsonPath('version.status', 'ready')
        ->assertJsonPath('version.sha256', 'sha256:'.hash('sha256', CACHING_NOTES))
        ->assertJsonPath('sections.1.heading', 'Cache-Control')
        ->assertJsonPath('sections.1.level', 2)
        ->assertJsonPath('sections.1.hash', 'sha256:'.TextHash::of("## Cache-Control\nThe no-store directive forbids storing the response."))
        ->assertJsonPath('sections.1.text', "## Cache-Control\nThe no-store directive forbids storing the response.")
        ->assertJsonMissingPath('version.storage_path');
});

it('escapes material text in the viewer', function (): void {
    ['instructor' => $instructor, 'course' => $course] = cohortWithStaff();
    $this->actingAs($instructor)->post(route('materials.store', $course), [
        'title' => 'Hostile',
        'file' => markdownUpload("# <script>alert(1)</script>\n<img src=x onerror=alert(2)>"),
    ]);
    $version = MaterialVersion::query()->sole();

    $this->get(route('materials.versions.show', [$course, $version->material, $version]))
        ->assertOk()
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertDontSee('<img src=x', false)
        ->assertSee('&lt;img src=x onerror=alert(2)&gt;', false);
});

describe('access', function (): void {
    it('lets admins and instructors upload, TAs only read and learners nothing', function (string $who, int $list, int $upload): void {
        $staff = cohortWithStaff();

        $this->actingAs($staff[$who])->get(route('materials.index', $staff['course']))->assertStatus($list);
        $this->actingAs($staff[$who])
            ->post(route('materials.store', $staff['course']), ['title' => 'Notes '.$who, 'file' => markdownUpload("# {$who}\ntext")])
            ->assertStatus($upload);
    })->with([
        'org admin' => ['admin', 200, 302],
        'instructor' => ['instructor', 200, 302],
        'TA' => ['ta', 200, 403],
        'learner' => ['learner', 403, 403],
    ]);

    it('hides the upload form from TAs', function (): void {
        ['ta' => $ta, 'course' => $course] = cohortWithStaff();

        $this->actingAs($ta)->get(route('materials.index', $course))
            ->assertOk()
            ->assertDontSee('Upload material');
    });

    it('keeps instructors to the courses they teach', function (): void {
        ['instructor' => $instructor, 'organization' => $organization] = cohortWithStaff();
        $otherCourse = Course::factory()->for($organization)->create();
        Cohort::factory()->for($otherCourse)->withMember($instructor, CohortRole::Learner)->create();

        $this->actingAs($instructor)->get(route('materials.index', $otherCourse))->assertForbidden();
        $this->actingAs($instructor)
            ->post(route('materials.store', $otherCourse), ['title' => 'X', 'file' => markdownUpload()])
            ->assertForbidden();
    });

    it('never crosses organizations', function (): void {
        $a = cohortWithStaff();
        $b = cohortWithStaff();
        $this->actingAs($a['instructor'])->post(route('materials.store', $a['course']), ['title' => 'A notes', 'file' => markdownUpload()]);
        $version = MaterialVersion::query()->sole();

        foreach (['admin', 'instructor', 'ta'] as $who) {
            $this->actingAs($b[$who])->get(route('materials.index', $a['course']))->assertForbidden();
            $this->actingAs($b[$who])->get(route('materials.versions.show', [$a['course'], $version->material, $version]))->assertForbidden();
            $this->actingAs($b[$who])->getJson(route('materials.versions.sections', [$a['course'], $version->material, $version]))->assertForbidden();
        }
    });

    it('scopes materials to their course and versions to their material', function (): void {
        ['admin' => $admin, 'organization' => $organization, 'course' => $course] = cohortWithStaff();
        $otherCourse = Course::factory()->for($organization)->create();
        $this->actingAs($admin);
        $this->post(route('materials.store', $course), ['title' => 'Notes', 'file' => markdownUpload()]);
        $this->post(route('materials.store', $course), ['title' => 'Other', 'file' => markdownUpload("# Other\ntext")]);
        [$first, $second] = Material::query()->orderBy('id')->get()->all();
        $this->post(route('materials.store', $course), ['material_id' => $first->id, 'file' => markdownUpload("# v2\ntext")]);
        $version = $first->versions()->where('version', 2)->sole();

        $this->get(route('materials.versions.show', [$course, $first, $version]))->assertOk();

        $this->get(route('materials.versions.show', [$otherCourse, $first, $version]))->assertNotFound();
        $this->get(route('materials.versions.show', [$course, $second, $version]))->assertNotFound();
    });

    it('links to materials from the dashboard for staff only', function (): void {
        ['instructor' => $instructor, 'learner' => $learner, 'course' => $course] = cohortWithStaff();
        $link = route('materials.index', $course);

        $this->actingAs($instructor)->get(route('dashboard'))->assertSee($link, false);
        $this->actingAs($learner)->get(route('dashboard'))->assertDontSee($link, false);
    });

    it('requires sign-in', function (): void {
        $course = Course::factory()->for(Organization::factory())->create();

        $this->get(route('materials.index', $course))->assertRedirect(route('login'));
    });
});

it('lets a second organization admin add versions too', function (): void {
    ['organization' => $organization, 'course' => $course, 'instructor' => $instructor] = cohortWithStaff();
    $secondAdmin = User::factory()->create();
    $organization->members()->attach($secondAdmin, ['role' => OrganizationRole::Admin->value]);

    $this->actingAs($instructor)->post(route('materials.store', $course), ['title' => 'Notes', 'file' => markdownUpload()]);
    $material = Material::query()->sole();

    $this->actingAs($secondAdmin)
        ->post(route('materials.store', $course), ['material_id' => $material->id, 'file' => markdownUpload("# v2\nupdated")])
        ->assertSessionHasNoErrors();

    expect($material->versions()->max('version'))->toBe(2);
});
