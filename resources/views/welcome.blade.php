<x-layouts.app>
    <section aria-labelledby="intro-heading" class="space-y-6">
        <h1 id="intro-heading" class="text-3xl font-semibold tracking-tight">
            {{ __('Practice what your course actually teaches.') }}
        </h1>
        <p class="max-w-2xl text-stone-700">
            {{ __('Instructors turn their own material into practice items they approve. Learners review a few items each day on a spaced-repetition schedule.') }}
        </p>

        <ul class="grid gap-4 sm:grid-cols-3">
            <li class="rounded-lg border border-stone-200 bg-white p-4">
                <h2 class="font-medium">{{ __('Instructors') }}</h2>
                <p class="mt-1 text-sm text-stone-600">{{ __('Upload material, review suggested items and publish decks.') }}</p>
            </li>
            <li class="rounded-lg border border-stone-200 bg-white p-4">
                <h2 class="font-medium">{{ __('Teaching assistants') }}</h2>
                <p class="mt-1 text-sm text-stone-600">{{ __('Resolve learner disputes and review flagged items.') }}</p>
            </li>
            <li class="rounded-lg border border-stone-200 bg-white p-4">
                <h2 class="font-medium">{{ __('Learners') }}</h2>
                <p class="mt-1 text-sm text-stone-600">{{ __('Short daily sessions with feedback tied to the source.') }}</p>
            </li>
        </ul>

        <p class="text-sm text-stone-500">{{ __('Early development build. All course data is synthetic.') }}</p>
    </section>
</x-layouts.app>
