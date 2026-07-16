<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Inline script to detect system dark mode preference and apply it immediately --}}
    <script>
        (function() {
            const appearance = "{{ $appearance ?? 'system' }}";

            if (appearance === 'system') {
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                if (prefersDark) {
                    document.documentElement.classList.add('dark');
                }
            }
        })();
    </script>

    {{-- Inline style to set the HTML background color based on our theme in app.css --}}
    {{-- noinspection CssUnusedSymbol --}}
    <style>
        html {
            background-color: oklch(1 0 0);
        }

        html.dark {
            background-color: oklch(0.145 0 0);
        }
    </style>

    @php($iconsLocale = app()->getLocale() === 'pl' ? 'pl' : 'en')
    <link rel="icon" href="/{{ $iconsLocale }}/favicon.ico" sizes="any">
    <link rel="icon" href="/{{ $iconsLocale }}/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/{{ $iconsLocale }}/apple-touch-icon.png">

    {{-- SEO Meta Tags are handled by Inertia Head component in Vue pages via @inertiaHead --}}
    @if(isset($page['props']['seo']))
        <title>{{ $page['props']['seo']['title'] ?? config('app.name') }}</title>
        <meta name="description" content="{{ $page['props']['seo']['description'] ?? '' }}">
        <meta name="robots" content="{{ $page['props']['seo']['robots'] ?? 'index, follow' }}">
        @if(isset($page['props']['seo']['canonicalUrl']))
            <link rel="canonical" href="{{ $page['props']['seo']['canonicalUrl'] }}">
        @endif
    @endif

    @routes
    @if(app()->environment('testing'))
        @vite(['resources/js/app.ts'])
    @else
        @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
    @endif
    @inertiaHead
</head>
<body class="antialiased">
@inertia
</body>
</html>
