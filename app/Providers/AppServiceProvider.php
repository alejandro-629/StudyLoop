<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\CurrentOrganization;
use App\Support\Materials\MaterialParser;
use App\Support\Materials\PdfTextExtractor;
use App\Support\Materials\Sectionizer;
use App\Support\Materials\TextNormalizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(CurrentOrganization::class);

        $this->app->bind(MaterialParser::class, fn (): MaterialParser => new MaterialParser(
            new TextNormalizer,
            new Sectionizer(max(1, (int) config('studyloop.materials.max_section_characters'))),
            new PdfTextExtractor(max(1, (int) config('studyloop.materials.max_pdf_pages'))),
            max(1, (int) config('studyloop.materials.max_text_characters')),
        ));
    }

    public function boot(): void
    {
        // Lazy loading, silently discarded attributes and missing attributes
        // throw outside production so N+1 queries and mass-assignment slips
        // surface in tests instead of in a learner's session. Every model
        // lists $fillable explicitly; nothing is unguarded globally.
        Model::shouldBeStrict(! $this->app->isProduction());

        DB::prohibitDestructiveCommands($this->app->isProduction());
    }
}
