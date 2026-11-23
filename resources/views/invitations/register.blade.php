<x-layouts.app :title="__('Join :cohort', ['cohort' => $cohort->name])">
    <section aria-labelledby="join-heading" class="mx-auto max-w-sm space-y-6">
        <div class="space-y-1">
            <h1 id="join-heading" class="text-2xl font-semibold tracking-tight">{{ __('Join :cohort', ['cohort' => $cohort->name]) }}</h1>
            <p class="text-stone-600">
                {{ $cohort->course->title }} · {{ $cohort->course->organization->name }} · {{ $invitation->role->label() }}
            </p>
        </div>

        <form method="POST" action="{{ $acceptUrl }}" class="space-y-4">
            @csrf
            <p class="text-sm">{{ __('Creating an account for :email', ['email' => $invitation->email]) }}</p>

            <div class="space-y-1">
                <label for="name" class="block text-sm font-medium">{{ __('Name') }}</label>
                <input id="name" name="name" value="{{ old('name') }}" autocomplete="name" required
                       @error('name') aria-invalid="true" aria-describedby="name-error" @enderror
                       class="w-full rounded border border-stone-300 px-3 py-2">
                @error('name')
                    <p id="name-error" class="text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <label for="password" class="block text-sm font-medium">{{ __('Password') }}</label>
                <input id="password" name="password" type="password" autocomplete="new-password" required
                       @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                       class="w-full rounded border border-stone-300 px-3 py-2">
                @error('password')
                    <p id="password-error" class="text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <label for="password_confirmation" class="block text-sm font-medium">{{ __('Confirm password') }}</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required
                       class="w-full rounded border border-stone-300 px-3 py-2">
            </div>

            <button type="submit" class="w-full rounded bg-stone-900 px-4 py-2 font-medium text-white hover:bg-stone-700">
                {{ __('Create account and join') }}
            </button>
        </form>
    </section>
</x-layouts.app>
