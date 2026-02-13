<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'SIWIRUS') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="dns-prefetch" href="https://fonts.bunny.net">

    <link rel="preload" href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" as="style">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @include('public.partials.theme-init')

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/react/main.jsx'])
</head>
<body class="bg-background text-foreground antialiased">
    <script type="application/json" id="public-initial-data">@json($initial ?? null)</script>
    <div
        id="react-public"
        data-page="{{ $page ?? 'home' }}"
        @isset($slug) data-slug="{{ $slug }}" @endisset
    ></div>
</body>
</html>
