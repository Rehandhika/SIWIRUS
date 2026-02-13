<div wire:poll.10s="refresh" 
     x-data="{ 
         isOpen: @entangle('isOpen'),
         showDetails: false 
     }"
     class="relative z-50">
    
    <!-- HUD Status Badge (Central Focus) -->
    <div class="group relative flex flex-col items-center justify-center cursor-pointer"
         @click="showDetails = !showDetails">
        
        <!-- Outer Glow Ring (Animated) -->
        <div class="absolute -inset-1 rounded-full opacity-40 blur-md transition-all duration-1000 animate-pulse"
             :class="isOpen ? 'bg-green-500' : 'bg-red-600'"></div>
        
        <!-- Main Pill -->
        <div :class="{
                'bg-slate-900/90 border-green-500/50 text-green-400': isOpen,
                'bg-slate-900/90 border-red-500/50 text-red-400': !isOpen
             }"
             class="relative flex items-center gap-3 px-6 py-2 rounded-full border shadow-[0_0_15px_rgba(0,0,0,0.5)] backdrop-blur-xl transition-all hover:scale-105 active:scale-95">
            
            <!-- Icon with Status Indicator -->
            <div class="relative flex items-center justify-center w-4 h-4">
                <span x-show="isOpen" class="absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75 animate-ping"></span>
                <i :class="isOpen ? 'fa-store' : 'fa-lock'" class="fas relative z-10 text-sm"></i>
            </div>
            
            <!-- Text Label -->
            <div class="flex flex-col items-start leading-none">
                <span class="text-[9px] uppercase tracking-widest text-slate-500 font-bold mb-0.5">Status</span>
                <span x-text="isOpen ? 'BUKA' : 'TUTUP'" 
                      class="font-mono text-xs font-bold tracking-widest glow-text"
                      :class="isOpen ? 'text-green-300 drop-shadow-[0_0_5px_rgba(74,222,128,0.5)]' : 'text-red-400 drop-shadow-[0_0_5px_rgba(248,113,113,0.5)]'">
                </span>
            </div>

            <!-- Chevron -->
            <i class="fas fa-chevron-down text-[10px] opacity-50 transition-transform duration-300"
               :class="{ 'rotate-180': showDetails }"></i>
        </div>
    </div>

    <!-- HUD Detail Panel (Centered Floating) -->
    <div x-show="showDetails"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-4 scale-90"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 -translate-y-4 scale-90"
         @click.away="showDetails = false"
         class="absolute top-full mt-4 left-1/2 -translate-x-1/2 w-80 bg-slate-900/95 backdrop-blur-2xl border border-white/10 rounded-2xl shadow-[0_0_50px_rgba(0,0,0,0.7)] overflow-hidden ring-1 ring-white/5 z-[60]"
         style="display: none;">
        
        <!-- Header Strip -->
        <div class="h-1 w-full" :class="isOpen ? 'bg-green-500 shadow-[0_0_10px_#22c55e]' : 'bg-red-500 shadow-[0_0_10px_#ef4444]'"></div>

        <!-- Content -->
        <div class="p-6 space-y-5">
            <!-- Main Status Text -->
            <div class="text-center">
                <h3 class="text-white font-bold text-lg mb-1" x-text="isOpen ? 'KOPERASI BUKA' : 'KOPERASI TUTUP'"></h3>
                <p class="text-xs text-slate-400 font-mono" x-text="isOpen ? 'Silakan datang bertransaksi' : 'Kami sedang tidak beroperasi'"></p>
            </div>

            <!-- Info Grid (Only show if closed or if there is specific reason other than default) -->
            <div x-show="!isOpen" class="grid grid-cols-1 gap-3 bg-white/5 p-4 rounded-xl border border-white/5">
                <div class="flex items-start gap-3">
                    <div class="mt-1"><i class="fas fa-info-circle text-blue-400 text-xs"></i></div>
                    <div>
                        <p class="text-[10px] uppercase tracking-wider text-slate-500 font-bold">Keterangan</p>
                        <p class="text-sm text-slate-200 leading-snug">{{ $reason }}</p>
                    </div>
                </div>

                @if(!$isOpen && $nextOpenTime)
                    <div class="flex items-start gap-3 pt-3 border-t border-white/5">
                        <div class="mt-1"><i class="fas fa-clock text-blue-400 text-xs"></i></div>
                        <div>
                            <p class="text-[10px] uppercase tracking-wider text-slate-500 font-bold">Buka Kembali</p>
                            <p class="text-sm text-slate-200">{{ $nextOpenTime }}</p>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Attendees (If Open) -->
            @if($isOpen && count($attendees) > 0)
                <div>
                    <p class="text-[10px] uppercase tracking-wider text-slate-500 font-bold mb-3 text-center">Petugas Jaga</p>
                    <div class="flex flex-wrap justify-center gap-2">
                        @foreach($attendees as $attendee)
                            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-green-500/10 border border-green-500/20 text-xs text-green-300">
                                <div class="w-1.5 h-1.5 bg-green-400 rounded-full animate-pulse"></div>
                                {{ $attendee }}
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
        
        <!-- Footer -->
        <div class="py-2 text-center border-t border-white/5 bg-black/20">
            <p class="text-[10px] text-slate-600 font-mono">LIVE SYSTEM STATUS</p>
        </div>
    </div>
</div>