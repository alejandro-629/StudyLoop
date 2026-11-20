<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name', 'StudyLoop') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-stone-50 text-stone-900 antialiased">
        <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:rounded focus:bg-white focus:px-3 focus:py-2">
            {{ __('Skip to content') }}
        </a>

        <header class="border-b border-stone-200 bg-white">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
                <a href="{{ route('home') }}" class="text-lg font-semibold tracking-tight">{{ config('app.name', 'StudyLoop') }}</a>
            </div>
        </header>

        <main id="main" class="mx-auto max-w-5xl px-6 py-10">
            {{ $slot }}
        </main>

        @livewireScripts
    </body>
</html>
