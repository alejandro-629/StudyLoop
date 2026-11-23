<x-layouts.app :title="__('Invitation unavailable')">
    <section aria-labelledby="unavailable-heading" class="mx-auto max-w-sm space-y-4">
        <h1 id="unavailable-heading" class="text-2xl font-semibold tracking-tight">{{ __('Invitation unavailable') }}</h1>
        <p class="text-stone-700">{{ $reason }}</p>
    </section>
</x-layouts.app>
