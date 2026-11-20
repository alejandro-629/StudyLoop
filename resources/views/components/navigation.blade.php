@inject('navigation', 'App\Support\Navigation')

<nav aria-label="{{ __('Main') }}" class="flex items-center gap-4 text-sm">
    @auth
        @foreach ($navigation->items(auth()->user()) as $item)
            <a href="{{ $item['url'] }}" @if ($item['active']) aria-current="page" @endif
               class="{{ $item['active'] ? 'font-semibold underline' : 'hover:underline' }}">{{ $item['label'] }}</a>
        @endforeach

        <span class="text-stone-500">
            {{ auth()->user()->name }}
            @if ($role = $navigation->roleLabel(auth()->user()))
                <span class="ml-1 rounded bg-stone-100 px-1.5 py-0.5 text-xs text-stone-700">{{ $role }}</span>
            @endif
        </span>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="rounded px-2 py-1 hover:bg-stone-100">{{ __('Sign out') }}</button>
        </form>
    @else
        <a href="{{ route('login') }}" class="hover:underline">{{ __('Sign in') }}</a>
    @endauth
</nav>
