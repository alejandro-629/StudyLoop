<x-layouts.app :title="__('Materials')">
    <section aria-labelledby="materials-heading" class="space-y-8">
        <div>
            <h1 id="materials-heading" class="text-2xl font-semibold tracking-tight">{{ __('Materials') }}</h1>
            <p class="text-stone-600">{{ $course->code }} · {{ $course->title }}</p>
        </div>

        @can('manageMaterials', $course)
            <form method="POST" action="{{ route('materials.store', $course) }}" enctype="multipart/form-data"
                  class="space-y-4 rounded-lg border border-stone-200 bg-white p-4">
                @csrf
                <h2 class="text-lg font-medium">{{ __('Upload material') }}</h2>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-1">
                        <label for="material_id" class="block text-sm font-medium">{{ __('Material') }}</label>
                        <select id="material_id" name="material_id" class="w-full rounded border border-stone-300 px-3 py-2">
                            <option value="">{{ __('New material') }}</option>
                            @foreach ($materials as $material)
                                <option value="{{ $material->id }}" @selected((string) old('material_id') === (string) $material->id)>
                                    {{ __('New version of “:title”', ['title' => $material->title]) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label for="title" class="block text-sm font-medium">{{ __('Title (new material only)') }}</label>
                        <input id="title" name="title" type="text" maxlength="200" value="{{ old('title') }}"
                               @error('title') aria-invalid="true" aria-describedby="title-error" @enderror
                               class="w-full rounded border border-stone-300 px-3 py-2">
                        @error('title')
                            <p id="title-error" class="text-sm text-red-700">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="space-y-1">
                    <label for="file" class="block text-sm font-medium">{{ __('File') }}</label>
                    <input id="file" name="file" type="file" required accept=".{{ implode(',.', $extensions) }}"
                           aria-describedby="file-help @error('file') file-error @enderror"
                           @error('file') aria-invalid="true" @enderror
                           class="block w-full text-sm">
                    <p id="file-help" class="text-sm text-stone-600">
                        {{ __('Markdown, plain text or PDF with a text layer, up to :size MB. Identical files are detected and not stored twice.', ['size' => round($maxUploadKb / 1024, 1)]) }}
                    </p>
                    @error('file')
                        <p id="file-error" class="text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="rounded bg-stone-900 px-4 py-2 font-medium text-white hover:bg-stone-700">{{ __('Upload') }}</button>
            </form>
        @endcan

        <div class="space-y-3">
            <h2 class="text-lg font-medium">{{ __('Course materials') }}</h2>
            @forelse ($materials as $material)
                <div class="space-y-2 rounded-lg border border-stone-200 bg-white p-4">
                    <h3 class="font-medium">{{ $material->title }}</h3>
                    <ul class="divide-y divide-stone-100 text-sm">
                        @foreach ($material->versions as $version)
                            <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                                <span>
                                    {{ __('Version :number', ['number' => $version->version]) }}
                                    · {{ $version->format->label() }}
                                    · {{ $version->original_name }}
                                    · {{ $version->status->label() }}
                                    @if ($version->failure_reason !== null)
                                        — <span class="text-red-700">{{ $version->failure_reason->label() }}</span>
                                    @endif
                                </span>
                                <a href="{{ route('materials.versions.show', [$course, $material, $version]) }}" class="font-medium hover:underline">{{ __('Sections') }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @empty
                <p class="text-sm text-stone-600">{{ __('No material uploaded yet.') }}</p>
            @endforelse
        </div>
    </section>
</x-layouts.app>
