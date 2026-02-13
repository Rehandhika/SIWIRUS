<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="robots" content="noindex, nofollow">
    
    {{-- Auto-refresh every 60 seconds to check if maintenance ended --}}
    <meta http-equiv="refresh" content="60">

    <title>{{ config('app.name', 'SIWIRUS') }} - Maintenance</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    
    <!-- Styles -->
    @vite(['resources/css/app.css'])
    <style>
        .font-grotesk { font-family: 'Space Grotesk', sans-serif; }
        .glass-card {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-slate-950 font-sans antialiased text-slate-300 selection:bg-amber-500/30 selection:text-amber-200">
    
    <!-- Ambient Dynamic Background -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none -z-10">
        <div class="absolute top-[-10%] left-1/4 w-[600px] h-[600px] bg-amber-900/10 rounded-full blur-[120px] animate-pulse"></div>
        <div class="absolute bottom-[-10%] right-1/4 w-[500px] h-[500px] bg-orange-900/10 rounded-full blur-[100px]"></div>
        <div class="absolute inset-0 bg-[url('https://grainy-gradients.vercel.app/noise.svg')] opacity-[0.03]"></div>
    </div>

    <div class="min-h-screen flex flex-col">
        
        <!-- Minimal Navbar -->
        <div class="sticky top-6 z-50 px-4 mb-8">
            <div class="max-w-7xl mx-auto">
                <nav class="glass-card rounded-2xl px-4 md:px-6 py-3 md:py-4 flex items-center justify-between">
                    
                    <!-- Brand / Logo -->
                    <div class="flex items-center gap-3 group">
                        <div class="relative w-10 h-10 flex items-center justify-center">
                            <img src="{{ asset('images/logo.png') }}" alt="SIWIRUS" class="w-10 h-10 rounded-xl object-cover shadow-lg">
                        </div>
                        <div class="flex flex-col">
                            <span class="font-grotesk font-bold text-lg text-white tracking-tight leading-none">SIWIRUS</span>
                            <span class="text-[10px] uppercase tracking-[0.2em] text-slate-500 hidden sm:block">Maintenance</span>
                        </div>
                    </div>

                    <!-- Status Badge -->
                    <div class="hidden md:flex items-center gap-2 px-4 py-2 rounded-xl bg-amber-500/10 border border-amber-500/20">
                        <span class="w-2 h-2 bg-amber-500 rounded-full animate-pulse"></span>
                        <span class="text-xs font-medium text-amber-400 uppercase tracking-wider">Maintenance Mode</span>
                    </div>

                    <!-- Login Button -->
                    <a href="{{ route('login') }}" 
                       class="relative group px-5 py-2 rounded-xl overflow-hidden">
                        <div class="absolute inset-0 bg-blue-600/20 group-hover:bg-blue-600/30 transition-colors"></div>
                        <div class="absolute inset-0 border border-blue-500/30 rounded-xl"></div>
                        <span class="relative text-sm font-medium text-blue-300 group-hover:text-white transition-colors flex items-center gap-2">
                            <i class="fas fa-sign-in-alt"></i>
                            <span class="hidden sm:inline">Login Admin</span>
                            <span class="sm:hidden">Login</span>
                        </span>
                    </a>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <main class="flex-1 flex items-center justify-center px-4 py-12">
            <div class="w-full max-w-lg text-center">
                
                <!-- Logo with Glow Effect -->
                <div class="relative w-32 h-32 mx-auto mb-8">
                    <div class="absolute inset-0 bg-amber-500/20 rounded-full blur-xl animate-pulse"></div>
                    <div class="relative w-full h-full flex items-center justify-center">
                        <img src="{{ asset('images/logo.png') }}" alt="SIWIRUS" class="w-24 h-24 rounded-2xl object-cover shadow-2xl">
                    </div>
                </div>

                <!-- Glass Card -->
                <div class="glass-card rounded-2xl p-8 md:p-10">
                    
                    <!-- Title -->
                    <h1 class="font-grotesk text-2xl md:text-3xl font-bold text-white mb-3">
                        Sedang Dalam Pemeliharaan
                    </h1>

                    <!-- Message -->
                    <p class="text-slate-400 mb-8 leading-relaxed">
                        {{ $message ?? 'Sistem sedang dalam pemeliharaan untuk meningkatkan layanan. Silakan coba beberapa saat lagi.' }}
                    </p>

                    <!-- Estimated End Time -->
                    @if(!empty($estimated_end))
                        <div class="bg-slate-800/50 rounded-xl p-5 mb-8 border border-slate-700/50">
                            <div class="flex items-center justify-center gap-3 mb-2">
                                <i class="fas fa-clock text-amber-400"></i>
                                <span class="text-sm text-slate-500 uppercase tracking-wider">Estimasi Selesai</span>
                            </div>
                            <p class="font-grotesk text-xl font-semibold text-white">
                                {{ \Carbon\Carbon::parse($estimated_end)->locale('id')->isoFormat('dddd, D MMMM Y') }}
                            </p>
                            <p class="text-amber-400 font-mono text-lg">
                                {{ \Carbon\Carbon::parse($estimated_end)->format('H:i') }} WIB
                            </p>
                        </div>
                    @endif

                    <!-- Auto-refresh notice -->
                    <div class="flex items-center justify-center gap-2 text-sm text-slate-500">
                        <div class="w-4 h-4 border-2 border-slate-600 border-t-amber-500 rounded-full animate-spin"></div>
                        <span>Halaman akan refresh otomatis setiap 60 detik</span>
                    </div>
                </div>

            </div>
        </main>

        <!-- Minimal Footer -->
        <footer class="mt-auto border-t border-white/5 bg-slate-950/80 backdrop-blur-lg">
            <div class="max-w-7xl mx-auto px-4 py-6">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-3 text-xs text-slate-500">
                    <div class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 bg-blue-500/60 rounded-full"></span>
                        <span class="tracking-wide">SIWIRUS</span>
                        <span class="text-slate-700">•</span>
                        <span>Divisi IT UKM Kewirausahaan STIS</span>
                    </div>
                    
                    <span class="font-mono text-slate-600">
                        © {{ date('Y') }}
                    </span>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>
