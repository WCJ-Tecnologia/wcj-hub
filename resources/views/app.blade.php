<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scrollbar-slim">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="{{ config('app.brand.theme_color') }}">
        <meta name="application-name" content="{{ config('app.name') }}">
        <meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">

        {{-- Ícone do WCJ Hub (docs/brand/logo/produtos/wcj-hub/icone). --}}
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
        <link rel="manifest" href="{{ asset('site.webmanifest') }}">

        <title inertia>{{ config('app.name') }}</title>

        {{-- Apply the persisted theme before paint to avoid a flash of the wrong colours.
             Inline, so it needs the CSP nonce issued by the SecurityHeaders middleware. --}}
        <script nonce="{{ Vite::cspNonce() }}">
            (function () {
                try {
                    var t = localStorage.getItem('app-theme');
                    var d = t ? t === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
                    if (d) document.documentElement.classList.add('dark');
                } catch (e) {}
            })();
        </script>

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx'])
        @inertiaHead
    </head>
    <body class="min-h-screen bg-background font-sans antialiased">
        @inertia
    </body>
</html>
