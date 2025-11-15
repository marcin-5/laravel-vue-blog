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

    {{-- noinspection HtmlUnknownAttribute --}}
    @if(isset($page['props']['seo']['title']))
        <title inertia>{{ $page['props']['seo']['title'] }}</title>
    @else
        <title inertia>{{ config('app.name', 'Laravel') }}</title>
    @endif

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    {{-- SEO Meta Tags for SSR --}}
    @if(isset($page['props']['seo']))
        @php($seo = $page['props']['seo'])

        <!-- Primary Meta Tags -->
        <meta name="description" content="{{ $seo['description'] }}">
        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
        <link rel="canonical" href="{{ $seo['canonicalUrl'] }}">
        <meta http-equiv="content-language" content="{{ $seo['locale'] }}">

        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="{{ $seo['ogType'] }}">
        <meta property="og:title" content="{{ $seo['title'] }}">
        <meta property="og:description" content="{{ $seo['description'] }}">
        <meta property="og:url" content="{{ $seo['canonicalUrl'] }}">
        <meta property="og:image" content="{{ $seo['ogImage'] }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:locale" content="{{ $seo['locale'] }}">
        @if(isset($seo['publishedTime']) && $seo['publishedTime'])
            <meta property="article:published_time" content="{{ $seo['publishedTime'] }}">
        @endif
        @if(isset($seo['modifiedTime']) && $seo['modifiedTime'])
            <meta property="article:modified_time" content="{{ $seo['modifiedTime'] }}">
        @endif

        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $seo['title'] }}">
        <meta name="twitter:description" content="{{ $seo['description'] }}">
        <meta name="twitter:image" content="{{ $seo['ogImage'] }}">

        <!-- Structured Data -->
        <script
            type="application/ld+json">{!! json_encode($seo['structuredData'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endif

    @routes
    @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
    @inertiaHead
</head>
<body class="font-sans antialiased">
@inertia
</body>
</html>
