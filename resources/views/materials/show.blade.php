<x-layouts.app :title="$material->title">
    <section aria-labelledby="material-heading" class="space-y-8">
        <div class="space-y-1">
            <p class="text-sm"><a href="{{ route('materials.index', $course) }}" class="hover:underline">← {{ __('Materials') }}</a></p>
            <h1 id="material-heading" class="text-2xl font-semibold tracking-tight">{{ $material->title }}</h1>
            <p class="text-stone-600">
                {{ $course->code }} · {{ __('Version :number', ['number' => $version->version]) }}
                · {{ $version->format->label() }} · {{ $version->status->label() }}
            </p>
            <p class="font-mono text-xs text-stone-500">sha256 {{ $version->sha256 }}</p>
        </div>

        @if ($versions->count() > 1)
            <nav aria-label="{{ __('Versions') }}" class="flex flex-wrap gap-2 text-sm">
                @foreach ($versions as $other)
                    <a href="{{ route('materials.versions.show', [$course, $material, $other]) }}"
                       @if ($other->is($version)) aria-current="page" @endif
                       class="rounded border px-2 py-1 {{ $other->is($version) ? 'border-stone-900 bg-stone-900 text-white' : 'border-stone-300 hover:bg-stone-100' }}">
                        {{ __('v:number', ['number' => $other->version]) }}
                    </a>
                @endforeach
            </nav>
        @endif

        @if ($version->status === \App\Enums\MaterialStatus::Pending)
            <p role="status" class="rounded border border-amber-200 bg-amber-50 px-4 py-2 text-sm">{{ __('This version is being parsed. Reload the page in a moment.') }}</p>
        @elseif ($version->status === \App\Enums\MaterialStatus::Failed)
            <p role="alert" class="rounded border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-900">
                {{ $version->failure_reason?->label() ?? __('Parsing failed.') }}
            </p>
        @else
            <p class="text-sm text-stone-600">
                {{ trans_choice(':count section|:count sections', $sections->count()) }}
                · {{ trans_choice(':count character|:count characters', (int) $version->character_count) }}
                @if ($range !== null)
                    · {{ __('Highlighting characters :start–:end', ['start' => $range['start'], 'end' => $range['end']]) }}
                @endif
            </p>

            <div class="grid gap-6 md:grid-cols-[14rem_1fr]">
                <nav aria-label="{{ __('Sections') }}" class="text-sm">
                    <ol class="space-y-1">
                        @foreach ($sections as $entry)
                            <li style="padding-left: {{ max(0, $entry['model']->level - 1) * 0.75 }}rem">
                                <a href="{{ route('materials.versions.show', [$course, $material, $version, 'section' => $entry['model']->position]) }}#section-{{ $entry['model']->position }}"
                                   @if ($entry['selected']) aria-current="true" @endif
                                   class="hover:underline {{ $entry['selected'] ? 'font-semibold' : '' }}">
                                    {{ $entry['model']->heading ?? __('Introduction') }}
                                </a>
                            </li>
                        @endforeach
                    </ol>
                </nav>

                <div class="space-y-4">
                    @foreach ($sections as $entry)
                        @php($section = $entry['model'])
                        <article id="section-{{ $section->position }}"
                                 class="rounded-lg border bg-white p-4 {{ $entry['selected'] ? 'border-amber-400 ring-2 ring-amber-200' : 'border-stone-200' }}">
                            <header class="mb-2 flex flex-wrap items-baseline justify-between gap-2">
                                <h2 class="font-medium">{{ $section->heading ?? __('Introduction') }}</h2>
                                <p class="font-mono text-xs text-stone-500">
                                    [{{ $section->start }}, {{ $section->end }}) · {{ substr($section->text_hash, 0, 12) }}
                                    @if ($entry['verified'])
                                        · <span class="text-emerald-700">{{ __('hash verified') }}</span>
                                    @else
                                        · <span class="font-semibold text-red-700">{{ __('hash mismatch') }}</span>
                                    @endif
                                </p>
                            </header>
                            <div class="whitespace-pre-wrap break-words text-sm leading-relaxed {{ $entry['selected'] ? 'bg-amber-50' : '' }}">@foreach ($entry['segments'] as $segment)@php($tag = $segment['highlighted'] ? 'mark' : 'span')<{{ $tag }} @class(['bg-amber-200' => $segment['highlighted']])>{{ $segment['text'] }}</{{ $tag }}>@endforeach</div>
                        </article>
                    @endforeach
                </div>
            </div>
        @endif
    </section>
</x-layouts.app>
