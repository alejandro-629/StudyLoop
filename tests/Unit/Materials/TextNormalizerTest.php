<?php

declare(strict_types=1);

use App\Enums\MaterialFailureReason;
use App\Support\Materials\MaterialParseFailed;
use App\Support\Materials\TextNormalizer;

it('normalizes line endings, trailing spaces and blank runs', function (): void {
    $input = "\xEF\xBB\xBF\n\n# Title  \r\nLine one\t\rLine two\u{2028}\n\n\n\nEnd\n\n";

    expect((new TextNormalizer)->normalize($input))->toBe("# Title\nLine one\nLine two\n\nEnd");
});

it('composes Unicode to NFC so equivalent text hashes the same', function (): void {
    $decomposed = "Cafe\u{0301} cre\u{0300}me";

    expect((new TextNormalizer)->normalize($decomposed))->toBe("Caf\u{00E9} cr\u{00E8}me");
});

it('drops control characters and zero-width spaces but keeps joiners and emoji', function (): void {
    $input = "a\x00b\x07c\u{200B}d\u{FEFF}e \u{0085}f 👩\u{200D}💻 g\tтекст";

    expect((new TextNormalizer)->normalize($input))->toBe("abcde f 👩\u{200D}💻 g\tтекст");
});

it('rejects invalid UTF-8', function (): void {
    (new TextNormalizer)->normalize("valid start \xC3\x28 broken");
})->throws(MaterialParseFailed::class, MaterialFailureReason::InvalidEncoding->value);

it('is idempotent', function (): void {
    $normalizer = new TextNormalizer;
    $once = $normalizer->normalize("  Indented first line\r\n\r\n\r\nSecond  \n");

    expect($normalizer->normalize($once))->toBe($once)
        ->and($once)->toBe("  Indented first line\n\nSecond");
});
