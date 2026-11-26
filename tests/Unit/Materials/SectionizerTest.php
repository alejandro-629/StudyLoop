<?php

declare(strict_types=1);

use App\Enums\MaterialFormat;
use App\Support\Materials\SectionDraft;
use App\Support\Materials\Sectionizer;
use App\Support\Materials\TextHash;

/**
 * @return list<string>
 */
function sectionTexts(string $text, array $sections): array
{
    return array_map(
        fn (SectionDraft $section): string => mb_substr($text, $section->start, $section->end - $section->start, 'UTF-8'),
        $sections,
    );
}

it('splits Markdown at ATX headings with a preamble section', function (): void {
    $text = "Intro paragraph.\n\n# HTTP caching\nCaches store responses.\n\n## Cache-Control\nUse no-store for secrets. ##\n\n### Validators ###\nETag and Last-Modified.";

    $parsed = (new Sectionizer)->split(MaterialFormat::Markdown, [$text]);

    expect(array_map(fn (SectionDraft $s): array => [$s->heading, $s->level], $parsed->sections))->toBe([
        [null, 0],
        ['HTTP caching', 1],
        ['Cache-Control', 2],
        ['Validators', 3],
    ])->and(sectionTexts($parsed->text, $parsed->sections))->toBe([
        'Intro paragraph.',
        "# HTTP caching\nCaches store responses.",
        "## Cache-Control\nUse no-store for secrets. ##",
        "### Validators ###\nETag and Last-Modified.",
    ]);
});

it('stores a hash of exactly the text each range selects', function (): void {
    $text = "# One\nalpha\n\n# Two\nbeta";
    $parsed = (new Sectionizer)->split(MaterialFormat::Markdown, [$text]);

    foreach ($parsed->sections as $section) {
        $slice = mb_substr($parsed->text, $section->start, $section->end - $section->start, 'UTF-8');
        expect($section->textHash)->toBe(TextHash::of($slice));
    }
});

it('ignores headings inside fenced code and requires a space after the hashes', function (): void {
    $text = "# Real\n```bash\n# not a heading\n```\n#hashtag is text\n~~~~\n## also code\n~~~~\n\n# Next";

    $parsed = (new Sectionizer)->split(MaterialFormat::Markdown, [$text]);

    expect(array_map(fn (SectionDraft $s): ?string => $s->heading, $parsed->sections))->toBe(['Real', 'Next']);
});

it('counts offsets in code points, not bytes', function (): void {
    $text = "# Ünïcödé 日本語\nПривет 👋 мир\n\n# Zwei\nÄrger";

    $parsed = (new Sectionizer)->split(MaterialFormat::Markdown, [$text]);

    expect($parsed->sections[1]->start)->toBe(mb_strlen("# Ünïcödé 日本語\nПривет 👋 мир\n\n"))
        ->and(sectionTexts($parsed->text, $parsed->sections)[1])->toBe("# Zwei\nÄrger")
        ->and($parsed->sections[1]->end)->toBe(mb_strlen($text));
});

it('keeps plain text in one section', function (): void {
    $parsed = (new Sectionizer)->split(MaterialFormat::Text, ["# looks like a heading\nbut this is plain text"]);

    expect($parsed->sections)->toHaveCount(1)
        ->and($parsed->sections[0]->heading)->toBeNull()
        ->and($parsed->sections[0]->level)->toBe(0);
});

it('makes one section per PDF page with text and skips blank pages', function (): void {
    $parsed = (new Sectionizer)->split(MaterialFormat::Pdf, ['First page', '', 'Third page']);

    expect($parsed->text)->toBe("First page\n\nThird page")
        ->and(array_map(fn (SectionDraft $s): ?string => $s->heading, $parsed->sections))->toBe(['Page 1', 'Page 3'])
        ->and(sectionTexts($parsed->text, $parsed->sections))->toBe(['First page', 'Third page']);
});

it('splits long sections at paragraph breaks and numbers the parts', function (): void {
    $paragraph = str_repeat('word ', 7).'end.';
    $text = "# Long\n".implode("\n\n", array_fill(0, 4, $paragraph));

    $parsed = (new Sectionizer(80))->split(MaterialFormat::Markdown, [$text]);
    $texts = sectionTexts($parsed->text, $parsed->sections);

    expect(array_map(fn (SectionDraft $s): ?string => $s->heading, $parsed->sections))
        ->toBe(['Long', 'Long (part 2)', 'Long (part 3)'])
        ->and($texts[0])->toBe("# Long\n".$paragraph)
        ->and($texts[1])->toBe($paragraph)
        ->and($texts[2])->toBe($paragraph."\n\n".$paragraph);

    foreach ($parsed->sections as $section) {
        expect($section->end - $section->start)->toBeLessThanOrEqual(80);
    }
});

it('falls back to a hard cut when a section has no break at all', function (): void {
    $text = str_repeat('x', 25);

    $parsed = (new Sectionizer(10))->split(MaterialFormat::Text, [$text]);

    expect(array_map(fn (SectionDraft $s): array => [$s->start, $s->end], $parsed->sections))
        ->toBe([[0, 10], [10, 20], [20, 25]]);
});

it('is deterministic', function (): void {
    $text = "# A\none\n\n## B\ntwo\n\n# C\nthree";

    expect((new Sectionizer)->split(MaterialFormat::Markdown, [$text]))
        ->toEqual((new Sectionizer)->split(MaterialFormat::Markdown, [$text]));
});
