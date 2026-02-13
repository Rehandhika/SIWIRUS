<div class="min-h-screen relative overflow-hidden">
    {{-- Ambient Background --}}
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-[500px] bg-blue-900/20 blur-[120px] pointer-events-none -z-10"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        
        <!-- Page Header -->
        <div class="text-center mb-16 relative z-10">
            <h1 class="text-5xl md:text-6xl font-bold text-white mb-6 tracking-tight">Tentang Kami</h1>
            <p class="text-lg text-slate-400 max-w-2xl mx-auto">Koperasi Mahasiswa - Melayani dengan Sepenuh Hati dalam semangat inovasi dan transparansi.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12 relative z-10">
            
            <!-- About Text Section -->
            <div class="bg-slate-900/60 backdrop-blur-xl border border-white/10 rounded-3xl p-8 md:p-10 shadow-2xl">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-12 h-12 rounded-2xl bg-blue-500/10 flex items-center justify-center border border-blue-500/20">
                        <i class="fas fa-building text-xl text-blue-400"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-white">Tentang Koperasi</h2>
                </div>
                
                <div class="prose prose-invert prose-lg max-w-none">
                    @if($storeSetting && $storeSetting->about_text)
                        <p class="text-slate-300 leading-relaxed whitespace-pre-line">{{ $storeSetting->about_text }}</p>
                    @else
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <i class="fas fa-edit text-4xl text-slate-700 mb-4"></i>
                            <p class="text-slate-500 italic">Informasi profil koperasi akan segera dilengkapi.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Contact Information Section -->
            <div class="bg-slate-900/60 backdrop-blur-xl border border-white/10 rounded-3xl p-8 md:p-10 shadow-2xl flex flex-col h-full">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-12 h-12 rounded-2xl bg-pink-500/10 flex items-center justify-center border border-pink-500/20">
                        <i class="fas fa-address-book text-xl text-pink-400"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-white">Informasi Kontak</h2>
                </div>
                
                <div class="space-y-6 flex-1">
                    <!-- Phone -->
                    @if($storeSetting && $storeSetting->contact_phone && $storeSetting->contact_phone !== '-')
                    <div class="group flex items-start p-4 rounded-xl hover:bg-white/5 transition-colors border border-transparent hover:border-white/5">
                        <div class="mr-4 mt-1 w-8 h-8 flex items-center justify-center rounded-lg bg-slate-800 text-slate-400 group-hover:bg-blue-500 group-hover:text-white transition-all">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wider text-slate-500 font-bold mb-1">Telepon</p>
                            <a href="tel:{{ $storeSetting->contact_phone }}" class="text-lg text-slate-200 hover:text-white transition-colors">
                                {{ $storeSetting->contact_phone }}
                            </a>
                        </div>
                    </div>
                    @endif

                    <!-- Email -->
                    @if($storeSetting && $storeSetting->contact_email && $storeSetting->contact_email !== '-')
                    <div class="group flex items-start p-4 rounded-xl hover:bg-white/5 transition-colors border border-transparent hover:border-white/5">
                        <div class="mr-4 mt-1 w-8 h-8 flex items-center justify-center rounded-lg bg-slate-800 text-slate-400 group-hover:bg-blue-500 group-hover:text-white transition-all">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wider text-slate-500 font-bold mb-1">Email</p>
                            <a href="mailto:{{ $storeSetting->contact_email }}" class="text-lg text-slate-200 hover:text-white transition-colors break-all">
                                {{ $storeSetting->contact_email }}
                            </a>
                        </div>
                    </div>
                    @endif

                    <!-- WhatsApp -->
                    @if($storeSetting && $storeSetting->contact_whatsapp && $storeSetting->contact_whatsapp !== '-')
                    <div class="group flex items-start p-4 rounded-xl hover:bg-white/5 transition-colors border border-transparent hover:border-white/5">
                        <div class="mr-4 mt-1 w-8 h-8 flex items-center justify-center rounded-lg bg-slate-800 text-slate-400 group-hover:bg-green-500 group-hover:text-white transition-all">
                            <i class="fab fa-whatsapp"></i>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wider text-slate-500 font-bold mb-1">WhatsApp</p>
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $storeSetting->contact_whatsapp) }}" 
                               target="_blank" 
                               class="text-lg text-slate-200 hover:text-white transition-colors">
                                {{ $storeSetting->contact_whatsapp }}
                            </a>
                        </div>
                    </div>
                    @endif

                    <!-- Address -->
                    @if($storeSetting && $storeSetting->contact_address && $storeSetting->contact_address !== '-')
                    <div class="group flex items-start p-4 rounded-xl hover:bg-white/5 transition-colors border border-transparent hover:border-white/5">
                        <div class="mr-4 mt-1 w-8 h-8 flex items-center justify-center rounded-lg bg-slate-800 text-slate-400 group-hover:bg-blue-500 group-hover:text-white transition-all">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wider text-slate-500 font-bold mb-1">Alamat</p>
                            <p class="text-lg text-slate-200 leading-relaxed">{{ $storeSetting->contact_address }}</p>
                        </div>
                    </div>
                    @endif

                    @if(!$storeSetting || 
                        ($storeSetting->contact_phone === '-' && 
                         $storeSetting->contact_email === '-' && 
                         $storeSetting->contact_whatsapp === '-' && 
                         $storeSetting->contact_address === '-'))
                        <div class="text-center py-10">
                             <p class="text-slate-500 italic">Informasi kontak belum ditambahkan.</p>
                        </div>
                    @endif
                </div>
            </div>

        </div>

        <!-- Operating Hours Section -->
        <div class="mt-8 bg-slate-900/60 backdrop-blur-xl border border-white/10 rounded-3xl p-8 md:p-10 shadow-2xl relative z-10">
            <div class="flex items-center gap-4 mb-8">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 flex items-center justify-center border border-emerald-500/20">
                    <i class="fas fa-clock text-xl text-emerald-400"></i>
                </div>
                <h2 class="text-2xl font-bold text-white">Jam Operasional</h2>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($operatingDays as $day)
                <div class="relative group p-5 rounded-2xl border transition-all duration-300 {{ $day['is_open'] ? 'bg-emerald-500/5 border-emerald-500/20 hover:bg-emerald-500/10' : 'bg-slate-800/30 border-white/5 hover:bg-slate-800/50' }}">
                    
                    @if($day['is_open'])
                        <div class="absolute top-4 right-4 w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_10px_#10b981]"></div>
                    @else
                        <div class="absolute top-4 right-4 w-2 h-2 rounded-full bg-slate-700"></div>
                    @endif

                    <p class="font-bold text-lg mb-2 {{ $day['is_open'] ? 'text-white' : 'text-slate-500' }}">{{ $day['name'] }}</p>
                    
                    @if($day['is_open'])
                        <p class="text-emerald-300 font-mono text-sm tracking-wide">
                            {{ $day['open'] }} - {{ $day['close'] }}
                        </p>
                    @else
                        <p class="text-slate-600 font-mono text-sm italic">Tutup</p>
                    @endif
                </div>
                @endforeach
            </div>

            <div class="mt-8 p-4 bg-blue-500/10 border border-blue-500/20 rounded-xl flex items-start gap-3">
                <i class="fas fa-info-circle text-blue-400 mt-0.5"></i>
                <p class="text-sm text-blue-200">
                    <strong class="text-blue-100">Catatan:</strong> Jam operasional dapat berubah sewaktu-waktu. Silakan cek status toko secara real-time di indikator HUD bagian atas halaman.
                </p>
            </div>
        </div>

    </div>
</div>
