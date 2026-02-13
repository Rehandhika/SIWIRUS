<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'SIWIRUS') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="dns-prefetch" href="https://fonts.bunny.net">
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com/ajax/libs">
    
    <!-- Preload Critical Assets -->
    <link rel="preload" href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" as="style">
    {{-- Assuming standard Vite output structure, can be optimized further with @vite directive logic --}}
    
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

    <!-- Styles -->
    @vite(['resources/css/app.css'])
    @livewireStyles
    <style>
        .font-grotesk { font-family: 'Space Grotesk', sans-serif; }
        .glass-nav {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        }
        .nav-link-glow {
            position: relative;
        }
        .nav-link-glow::after {
            content: '';
            position: absolute;
            width: 0;
            height: 1px;
            bottom: -2px;
            left: 50%;
            background: linear-gradient(90deg, transparent, #3b82f6, transparent);
            transition: width 0.3s ease, left 0.3s ease;
        }
        .nav-link-glow:hover::after {
            width: 100%;
            left: 0;
        }
    </style>
</head>
<body class="bg-slate-950 font-sans antialiased text-slate-300 selection:bg-blue-500/30 selection:text-blue-200">
    
    <!-- Ambient Dynamic Background -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none -z-10">
        <div class="absolute top-[-10%] left-1/4 w-[600px] h-[600px] bg-blue-900/10 rounded-full blur-[120px] animate-pulse"></div>
        <div class="absolute bottom-[-10%] right-1/4 w-[500px] h-[500px] bg-blue-900/10 rounded-full blur-[100px]"></div>
        <div class="absolute inset-0 bg-[url('https://grainy-gradients.vercel.app/noise.svg')] opacity-[0.03]"></div>
    </div>

    <div class="min-h-screen flex flex-col">
        
        <!-- Creative Floating Navbar -->
        <div class="sticky top-6 z-50 px-4 mb-8" x-data="{ mobileMenuOpen: false }">
            <div class="max-w-7xl mx-auto">
                <nav class="glass-nav rounded-2xl px-4 md:px-6 py-3 md:py-4 flex items-center justify-between transition-all duration-300 hover:border-white/10 hover:shadow-[0_0_20px_rgba(99,102,241,0.15)] relative">
                    
                    <!-- Left: Brand / Logo -->
                    <a href="{{ route('home') }}" class="flex items-center gap-3 group z-10 w-1/3" wire:navigate>
                        <img src="{{ asset('images/logo.png') }}" alt="SIWIRUS" class="h-9 w-auto object-contain transition-transform group-hover:scale-105">
                        <div class="flex flex-col">
                            <span class="font-grotesk font-bold text-lg text-white tracking-tight leading-none group-hover:text-blue-300 transition-colors">SIWIRUS</span>
                            <span class="text-[10px] uppercase tracking-[0.15em] text-slate-500 hidden sm:block">UKM Kewirausahaan STIS</span>
                        </div>
                    </a>

                    <!-- Center: HUD Status (Visible on Desktop) -->
                    <div class="hidden md:flex justify-center w-1/3 z-20">
                         @livewire('public.store-status')
                    </div>

                    <!-- Right: Desktop Menu -->
                    <div class="hidden md:flex items-center justify-end gap-6 w-1/3 z-10">
                        <div class="flex items-center gap-6">
                            <a href="{{ route('home') }}" 
                               wire:navigate
                               class="nav-link-glow text-sm font-medium transition-colors {{ request()->routeIs('home') ? 'text-white' : 'text-slate-400 hover:text-white' }}">
                                Katalog
                            </a>
                            <a href="{{ route('public.about') }}" 
                               wire:navigate
                               class="nav-link-glow text-sm font-medium transition-colors {{ request()->routeIs('public.about') ? 'text-white' : 'text-slate-400 hover:text-white' }}">
                                Tentang
                            </a>
                        </div>
                        
                        <!-- Login Button -->
                        <a href="{{ route('login') }}" 
                           wire:navigate
                           class="relative group px-5 py-2 rounded-xl overflow-hidden">
                            <div class="absolute inset-0 bg-blue-600/20 group-hover:bg-blue-600/30 transition-colors"></div>
                            <div class="absolute inset-0 border border-blue-500/30 rounded-xl"></div>
                            <span class="relative text-sm font-medium text-blue-300 group-hover:text-white transition-colors">Login</span>
                        </a>
                    </div>

                    <!-- Right: Mobile Controls -->
                    <div class="flex md:hidden items-center gap-3">
                        <!-- Mobile Status (Compact) -->
                        <div class="scale-90">
                            @livewire('public.store-status')
                        </div>

                        <!-- Mobile Menu Button -->
                        <button @click="mobileMenuOpen = !mobileMenuOpen" class="w-10 h-10 flex items-center justify-center text-slate-300 hover:text-white bg-white/5 rounded-xl border border-white/5">
                            <i class="fas" :class="mobileMenuOpen ? 'fa-times' : 'fa-bars'"></i>
                        </button>
                    </div>
                </nav>

                <!-- Mobile Menu Dropdown -->
                <div x-show="mobileMenuOpen" 
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-4 scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                     class="absolute top-full left-0 right-0 mt-2 px-4 md:hidden z-40"
                     style="display: none;">
                    <div class="glass-nav rounded-2xl p-4 flex flex-col gap-2">
                        <a href="{{ route('home') }}" wire:navigate class="p-3 rounded-xl bg-white/5 text-slate-200 font-medium hover:bg-white/10">Katalog</a>
                        <a href="{{ route('public.about') }}" wire:navigate class="p-3 rounded-xl bg-white/5 text-slate-200 font-medium hover:bg-white/10">Tentang</a>
                        <a href="{{ route('login') }}" wire:navigate class="p-3 rounded-xl bg-blue-600 text-white font-medium text-center shadow-lg shadow-blue-500/20">Login System</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <main class="flex-1">
            {{ $slot ?? '' }}
            @yield('content')
        </main>

        <!-- Minimal Future Footer -->
        <footer class="mt-auto border-t border-white/5 bg-slate-950/80 backdrop-blur-lg">
            <div class="max-w-7xl mx-auto px-6 py-10">
                <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
                        <span class="text-xs font-grotesk text-slate-500 tracking-widest uppercase">SIWIRUS System v3.0</span>
                    </div>
                    
                    <div class="flex gap-6">
                        <a href="#" class="text-slate-600 hover:text-blue-400 transition-colors"><i class="fab fa-instagram text-lg"></i></a>
                        <a href="#" class="text-slate-600 hover:text-blue-400 transition-colors"><i class="fab fa-whatsapp text-lg"></i></a>
                        <a href="#" class="text-slate-600 hover:text-blue-400 transition-colors"><i class="fas fa-globe text-lg"></i></a>
                    </div>

                    <p class="text-xs text-slate-600 font-mono">
                        {{ date('Y') }} &copy; Developed for Students
                    </p>
                </div>
            </div>
        </footer>
    </div>

    @vite(['resources/js/app.js'])
    @livewireScripts
    @stack('scripts')
</body>
</html>