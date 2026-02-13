<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'SIWIRUS') }} - Login</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-gray-50 h-full w-full overflow-hidden flex items-center justify-center relative">
    <!-- Back Button -->
    <a href="{{ route('home') }}" class="absolute top-4 left-4 flex items-center gap-2 p-2 text-gray-400 hover:text-gray-600 transition-colors group" title="Kembali ke Katalog">
        <div class="p-1 rounded-full group-hover:bg-gray-100 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m15 18-6-6 6-6"/>
            </svg>
        </div>
        <span class="text-sm font-medium">Kembali</span>
    </a>

    {{ $slot }}

    @livewireScripts
</body>
</html>
