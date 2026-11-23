<div>
    @if ($organizations->count() > 1)
        <label for="organization-switcher" class="sr-only">{{ __('Current organization') }}</label>
        <select id="organization-switcher" wire:change="switchTo($event.target.value)"
                class="rounded border border-stone-300 bg-white px-2 py-1 text-sm">
            @foreach ($organizations as $organization)
                <option value="{{ $organization->id }}" @selected($current?->is($organization))>{{ $organization->name }}</option>
            @endforeach
        </select>
    @elseif ($current !== null)
        <span class="text-sm text-stone-600">{{ $current->name }}</span>
    @endif
</div>
