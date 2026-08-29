<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>

    {{-- Fonts: Inter (UI), JetBrains Mono (numbers), Inter Tight (display) --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|inter-tight:500,600,700|jetbrains-mono:400,500,600" rel="stylesheet">

    {{-- Anti-FOUC: set .dark class before body paints --}}
    <script>
        (function() {
            try {
                var stored = localStorage.getItem('mudaraba-theme') || 'system';
                var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                var isDark = stored === 'dark' || (stored === 'system' && prefersDark);
                if (isDark) document.documentElement.classList.add('dark');
            } catch (e) {}
        })();
    </script>

    {{-- Inertia Head --}}
    @routes
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.tsx'])
    @inertiaHead
</head>
<body class="h-full bg-background text-foreground antialiased">
    @inertia
</body>
</html>
