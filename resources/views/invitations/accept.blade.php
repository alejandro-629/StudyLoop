<x-layouts.app :title="__('Join :cohort', ['cohort' => $cohort->name])">
    <section aria-labelledby="join-heading" class="mx-auto max-w-sm space-y-6">
        <div class="space-y-1">
            <h1 id="join-heading" class="text-2xl font-semibold tracking-tight">{{ __('Join :cohort', ['cohort' => $cohort->name]) }}</h1>
            <p class="text-stone-600">
                {{ $cohort->course->title }} · {{ $cohort->course->organization->name }} · {{ $invitation->role->label() }}
            </p>
        </div>

        <form method="POST" action="{{ $acceptUrl }}">
            @csrf
            <button type="submit" class="w-full rounded bg-stone-900 px-4 py-2 font-medium text-white hover:bg-stone-700">
                {{ __('Accept invitation') }}
            </button>
        </form>
    </section>
</x-layouts.app>
