<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'SIWIRUS') }} - @yield('title', 'Dashboard')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Styles -->
    @vite(['resources/css/app.css'])
    @livewireStyles
</head>
<body class="bg-gray-50 font-sans antialiased">
    <div class="min-h-screen flex" x-data="{ sidebarOpen: false }">
        <!-- Mobile sidebar backdrop -->
        <div x-show="sidebarOpen" 
             @click="sidebarOpen = false"
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-600 bg-opacity-75 z-20 md:hidden"
             x-cloak></div>

        <!-- Sidebar -->
        <aside class="fixed inset-y-0 left-0 z-30 w-64 transform transition-transform duration-300 ease-in-out md:translate-x-0"
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
               role="navigation"
               aria-label="Main navigation">
            <div class="flex flex-col h-full bg-white border-r border-gray-200 shadow-sm overflow-hidden">
                <!-- Logo -->
                <div class="flex items-center justify-between px-4 py-5 border-b border-gray-200 flex-shrink-0">
                    <div class="flex items-center space-x-2">
                        <img src="{{ asset('images/logo.png') }}" alt="SIWIRUS" class="w-8 h-8 rounded-lg flex-shrink-0 object-cover">
                        <span class="text-xl font-semibold text-gray-900">SIWIRUS</span>
                    </div>
                    <button @click="sidebarOpen = false" 
                            type="button"
                            class="md:hidden text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-lg p-1 transition-colors"
                            aria-label="Close sidebar">
                        <x-ui.icon name="x" class="w-6 h-6" />
                    </button>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 px-2 py-4 space-y-1 overflow-y-auto overflow-x-hidden scrollbar-thin" aria-label="Sidebar navigation">
                    @include('components.navigation')
                </nav>

                <!-- User Menu -->
                <div class="flex-shrink-0 border-t border-gray-200 p-4 bg-gray-50">
                    @auth
                        <div class="flex items-center w-full">
                            <x-ui.avatar 
                                :name="auth()->user()->name" 
                                size="sm" 
                                class="flex-shrink-0"
                            />
                            <div class="ml-3 flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-gray-500 truncate">{{ auth()->user()->nim }}</p>
                            </div>
                            <form method="POST" action="{{ route('admin.logout') }}" class="ml-3">
                                @csrf
                                <button type="submit" 
                                        class="text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-lg p-1 transition-colors" 
                                        title="Logout"
                                        aria-label="Logout">
                                    <x-ui.icon name="arrow-right-on-rectangle" class="w-5 h-5" />
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="text-center text-sm text-gray-500">
                            <p class="mb-2">Belum login.</p>
                            <a href="{{ route('login') }}" 
                               class="text-primary-600 hover:text-primary-700 font-medium transition-colors">
                                Ke halaman login
                            </a>
                        </div>
                    @endauth
                </div>
            </div>
        </aside>

        <!-- Main content -->
        <div class="flex-1 md:ml-64 flex flex-col min-h-screen min-w-0 overflow-x-hidden">
            {{-- Maintenance Mode Warning Banner for Admins (inside main content area) --}}
            <x-layout.maintenance-banner />
            
            <!-- Top bar for mobile -->
            <header class="sticky top-0 z-10 bg-white border-b border-gray-200 md:hidden shadow-sm">
                <div class="flex items-center justify-between px-4 py-3">
                    <button @click="sidebarOpen = true" 
                            type="button" 
                            class="text-gray-500 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-lg p-1 transition-colors"
                            aria-label="Open sidebar">
                        <x-ui.icon name="bars-3" class="h-6 w-6" />
                    </button>
                    <span class="text-lg font-semibold text-gray-900">SIWIRUS</span>
                    <a href="{{ route('admin.notifications.index') }}" 
                        class="relative p-1 text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-lg transition-colors"
                        aria-label="Notifikasi">
                        <x-ui.icon name="bell" class="w-5 h-5" />
                        @php
                            $unreadCount = 0;
                            if (auth()->check()) {
                                try {
                                    $unreadCount = auth()->user()->notifications()->whereNull('read_at')->count();
                                } catch (\Exception $e) {
                                    $unreadCount = 0;
                                }
                            }
                        @endphp
                        @if($unreadCount > 0)
                            <span class="absolute -top-1 -right-1 min-w-[1rem] h-4 px-1 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center">
                                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                            </span>
                        @endif
                    </a>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 min-w-0 overflow-x-hidden">
                <div class="py-4 sm:py-6">
                    <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8">
                        {{ $slot ?? '' }}
                        @yield('content')
                    </div>
                </div>
            </main>
        </div>
    </div>

    @vite(['resources/js/app.js'])
    @livewireScripts
    
    {{-- Toast Notifications --}}
    <x-ui.toast />
    
    @stack('scripts')
</body>
</html>
