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
    <style>
        html {
            background-color: oklch(1 0 0);
        }

        /* noinspection CssUnusedSymbol */
        html.dark {
            background-color: oklch(0.145 0 0);
        }
    </style>

    @php($iconsLocale = app()->getLocale() === 'pl' ? 'pl' : 'en')
    <link rel="icon" href="/{{ $iconsLocale }}/favicon.ico" sizes="any">
    <link rel="icon" href="/{{ $iconsLocale }}/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/{{ $iconsLocale }}/apple-touch-icon.png">

    <title>{{ config('app.name', 'Laravel') }}</title>

    @routes
    @vite(['resources/js/app.ts'])
    @inertiaHead
</head>
<body class="antialiased">
@inertia
</body>
</html>
