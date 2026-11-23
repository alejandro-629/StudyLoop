<x-layouts.app :title="__('Invitations')">
    <section aria-labelledby="invitations-heading" class="space-y-8">
        <div>
            <h1 id="invitations-heading" class="text-2xl font-semibold tracking-tight">{{ __('Invitations') }}</h1>
            <p class="text-stone-600">{{ $cohort->course->code }} · {{ $cohort->name }}</p>
        </div>

        <form method="POST" action="{{ route('cohorts.invitations.store', $cohort) }}" class="grid gap-4 rounded-lg border border-stone-200 bg-white p-4 sm:grid-cols-[1fr_auto_auto] sm:items-end">
            @csrf
            <div class="space-y-1">
                <label for="email" class="block text-sm font-medium">{{ __('Email') }}</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required
                       @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                       class="w-full rounded border border-stone-300 px-3 py-2">
                @error('email')
                    <p id="email-error" class="text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>
            <div class="space-y-1">
                <label for="role" class="block text-sm font-medium">{{ __('Role') }}</label>
                <select id="role" name="role" class="rounded border border-stone-300 px-3 py-2">
                    @foreach ($roles as $role)
                        <option value="{{ $role->value }}" @selected(old('role', 'learner') === $role->value)>{{ $role->label() }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rounded bg-stone-900 px-4 py-2 font-medium text-white hover:bg-stone-700">{{ __('Send invitation') }}</button>
        </form>

        <div class="space-y-3">
            <h2 class="text-lg font-medium">{{ __('Pending') }}</h2>
            @forelse ($invitations as $invitation)
                <div class="flex items-center justify-between rounded border border-stone-200 bg-white px-4 py-3 text-sm">
                    <span>{{ $invitation->email }} · {{ $invitation->role->label() }} · {{ __('expires :date', ['date' => $invitation->expires_at->toFormattedDateString()]) }}</span>
                    <form method="POST" action="{{ route('cohorts.invitations.destroy', [$cohort, $invitation]) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="font-medium text-red-700 hover:underline">{{ __('Withdraw') }}</button>
                    </form>
                </div>
            @empty
                <p class="text-sm text-stone-600">{{ __('No pending invitations.') }}</p>
            @endforelse
        </div>
    </section>
</x-layouts.app>
