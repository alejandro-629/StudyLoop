<section aria-labelledby="login-heading" class="mx-auto max-w-sm space-y-6">
    <h1 id="login-heading" class="text-2xl font-semibold tracking-tight">{{ __('Sign in') }}</h1>

    <form wire:submit="login" class="space-y-4" novalidate>
        <div class="space-y-1">
            <label for="email" class="block text-sm font-medium">{{ __('Email') }}</label>
            <input wire:model="email" id="email" type="email" autocomplete="username" required autofocus
                   @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                   class="w-full rounded border border-stone-300 px-3 py-2">
            @error('email')
                <p id="email-error" class="text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>

        <div class="space-y-1">
            <label for="password" class="block text-sm font-medium">{{ __('Password') }}</label>
            <input wire:model="password" id="password" type="password" autocomplete="current-password" required
                   @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                   class="w-full rounded border border-stone-300 px-3 py-2">
            @error('password')
                <p id="password-error" class="text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input wire:model="remember" type="checkbox">
            {{ __('Keep me signed in') }}
        </label>

        <button type="submit" class="w-full rounded bg-stone-900 px-4 py-2 font-medium text-white hover:bg-stone-700">
            {{ __('Sign in') }}
        </button>
    </form>

    <p class="text-sm text-stone-600">{{ __('StudyLoop is invitation-only. Ask your instructor for an invitation link.') }}</p>
</section>
