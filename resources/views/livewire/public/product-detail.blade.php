<div class="pb-20">
    {{-- Breadcrumb (Minimal) --}}
    <div class="border-b border-white/5 bg-slate-900/30 backdrop-blur-sm py-4">
        <div class="max-w-7xl mx-auto px-4">
            <nav class="flex items-center space-x-3 text-sm">
                <a href="{{ route('home') }}" class="text-slate-500 hover:text-blue-400 transition-colors">
                    <i class="fas fa-home"></i>
                </a>
                <span class="text-slate-700">/</span>
                @if($product->category)
                    <span class="text-slate-500">{{ $product->category }}</span>
                    <span class="text-slate-700">/</span>
                @endif
                <span class="text-slate-200 font-medium truncate max-w-[200px]">{{ $product->name }}</span>
            </nav>
        </div>
    </div>

    {{-- Content --}}
    <div class="max-w-7xl mx-auto px-4 py-6 lg:py-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-10">
            
            {{-- Left Column: Visuals (5 cols) --}}
            <div class="lg:col-span-5 space-y-4 lg:space-y-6">
                <div class="aspect-square bg-slate-800 rounded-2xl lg:rounded-3xl overflow-hidden border border-white/10 relative group shadow-2xl shadow-black/50">
                    @if($product->hasImage())
                        <img 
                            src="{{ $product->image_large_url }}" 
                            alt="{{ $product->name }}" 
                            class="w-full h-full object-cover" 
                            fetchpriority="high"
                            decoding="async"
                            onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'w-full h-full flex items-center justify-center text-slate-600\'><i class=\'fas fa-image text-5xl opacity-30\'></i></div>';"
                        />
                    @else
                        <div class="w-full h-full flex items-center justify-center text-slate-600">
                            <i class="fas fa-image text-5xl opacity-30"></i>
                        </div>
                    @endif

                    {{-- Badges --}}
                    <div class="absolute top-4 left-4 flex flex-col gap-2">
                        @if($product->is_featured)
                            <span class="inline-flex items-center px-3 py-1 rounded-full bg-yellow-500/20 border border-yellow-500/30 text-yellow-300 text-xs font-bold uppercase tracking-wider backdrop-blur-md">
                                <i class="fas fa-star mr-1.5"></i> Unggulan
                            </span>
                        @endif
                    </div>

                    {{-- Variant Count Badge --}}
                    @if($product->has_variants && $product->variant_count > 0)
                        <div class="absolute bottom-4 left-4">
                            <span class="inline-flex items-center px-3 py-1 rounded-full bg-blue-500/20 border border-blue-500/30 text-blue-300 text-xs font-bold backdrop-blur-md">
                                <i class="fas fa-layer-group mr-1.5"></i> {{ $product->variant_count }} Varian
                            </span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Right Column: Info & Actions (7 cols) --}}
            <div class="lg:col-span-7">
                <div class="bg-slate-900/60 backdrop-blur-xl border border-white/10 rounded-2xl lg:rounded-3xl p-5 lg:p-8 shadow-xl relative overflow-hidden">
                    
                    {{-- Decorative Blur --}}
                    <div class="absolute top-0 right-0 w-64 h-64 bg-blue-500/10 rounded-full blur-[80px] -z-10"></div>

                    {{-- Category & SKU --}}
                    <div class="flex items-center justify-between mb-4">
                        @if($product->category)
                            <span class="px-3 py-1 bg-white/5 text-blue-300 text-xs font-semibold rounded-full border border-white/10">
                                {{ $product->category }}
                            </span>
                        @endif
                        @if($this->displaySku)
                            <span class="text-xs font-mono text-slate-500">SKU: {{ $this->displaySku }}</span>
                        @endif
                    </div>

                    {{-- Title --}}
                    <h1 class="text-2xl md:text-3xl lg:text-4xl font-bold text-white mb-2 leading-tight">{{ $product->name }}</h1>

                    {{-- Selected Variant Name --}}
                    @if($product->has_variants && $this->selectedVariant)
                        <p class="text-sm text-blue-400 mb-4">
                            Varian: {{ collect($this->selectedVariant->option_values)->pluck('value')->implode(' / ') }}
                        </p>
                    @endif

                    {{-- Price Section (Highlight) --}}
                    <div class="bg-slate-950/50 rounded-2xl p-5 lg:p-6 border border-white/5 mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 sm:gap-0">
                        <div>
                            <p class="text-sm text-slate-400 mb-1">
                                {{ $product->has_variants ? 'Harga Varian' : 'Harga Satuan' }}
                            </p>
                            <div class="text-3xl lg:text-4xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-cyan-400">
                                Rp {{ number_format($this->displayPrice, 0, ',', '.') }}
                            </div>
                            @if($product->has_variants)
                                @php
                                    $priceRange = $product->price_range;
                                    $hasRange = $priceRange['min'] !== $priceRange['max'];
                                @endphp
                                @if($hasRange)
                                    <p class="text-xs text-slate-500 mt-1">
                                        Range: Rp {{ number_format($priceRange['min'], 0, ',', '.') }} - Rp {{ number_format($priceRange['max'], 0, ',', '.') }}
                                    </p>
                                @endif
                            @endif
                        </div>
                        <div class="text-left sm:text-right pt-4 sm:pt-0 border-t sm:border-t-0 border-white/5">
                            <p class="text-sm text-slate-400 mb-2">Status Stok</p>
                            @php
                                $displayStock = $this->displayStock;
                                $isOutOfStock = $displayStock <= 0;
                                $isLowStock = !$isOutOfStock && $displayStock <= ($product->min_stock ?? 5);
                            @endphp
                            @if($isOutOfStock)
                                <span class="inline-block px-3 py-1 bg-red-500/20 text-red-400 text-sm font-medium rounded-lg border border-red-500/20">Habis</span>
                            @elseif($isLowStock)
                                <span class="inline-block px-3 py-1 bg-orange-500/20 text-orange-400 text-sm font-medium rounded-lg border border-orange-500/20">Sisa {{ $displayStock }}</span>
                            @else
                                <span class="inline-block px-3 py-1 bg-green-500/20 text-green-400 text-sm font-medium rounded-lg border border-green-500/20">Tersedia ({{ $displayStock }})</span>
                            @endif
                            @if($product->has_variants)
                                <p class="text-xs text-slate-500 mt-1">Total: {{ $product->total_stock }} unit</p>
                            @endif
                        </div>
                    </div>

                    {{-- Variant Selector (Grouped by Option Type) --}}
                    @if($product->has_variants && $product->activeVariants->isNotEmpty())
                        <div class="bg-slate-950/30 rounded-2xl p-5 border border-white/5 mb-6">
                            <h3 class="text-sm font-medium text-slate-400 uppercase tracking-wider mb-4">
                                Pilih Varian ({{ $product->activeVariants->count() }} tersedia)
                            </h3>
                            
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2">
                                @foreach($product->activeVariants as $variant)
                                    @php
                                        $isSelected = $selectedVariantId === $variant->id;
                                        $isOutOfStockVariant = $variant->stock <= 0;
                                        $optionLabel = collect($variant->option_values)->pluck('value')->implode(' / ');
                                    @endphp
                                    <button
                                        wire:click="selectVariant({{ $variant->id }})"
                                        @if($isOutOfStockVariant) disabled @endif
                                        class="relative p-3 rounded-xl border-2 transition-all duration-200 text-left
                                            {{ $isSelected 
                                                ? 'border-blue-500 bg-blue-500/10 ring-2 ring-blue-500/20' 
                                                : 'border-white/10 bg-slate-900/40 hover:border-blue-500/50 hover:bg-slate-900/60'
                                            }}
                                            {{ $isOutOfStockVariant ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer' }}"
                                    >
                                        {{-- Selected Indicator --}}
                                        @if($isSelected)
                                            <div class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-blue-500 rounded-full flex items-center justify-center">
                                                <i class="fas fa-check text-white text-[10px]"></i>
                                            </div>
                                        @endif
                                        
                                        {{-- Variant Label --}}
                                        <div class="font-medium text-slate-200 text-sm truncate">
                                            {{ $optionLabel ?: $variant->variant_name }}
                                        </div>
                                        
                                        {{-- Price and Stock --}}
                                        <div class="mt-1 flex items-center justify-between gap-2">
                                            <span class="text-xs font-semibold text-blue-400">
                                                Rp {{ number_format($variant->price, 0, ',', '.') }}
                                            </span>
                                            @if($isOutOfStockVariant)
                                                <span class="text-[10px] text-red-400 font-medium px-1.5 py-0.5 bg-red-500/10 rounded">Habis</span>
                                            @elseif($variant->stock <= ($variant->min_stock ?? 5))
                                                <span class="text-[10px] text-orange-400 font-medium">Sisa {{ $variant->stock }}</span>
                                            @else
                                                <span class="text-[10px] text-emerald-400 font-medium">Stok {{ $variant->stock }}</span>
                                            @endif
                                        </div>
                                    </button>
                                @endforeach
                            </div>

                            {{-- Out of Stock Variants Summary --}}
                            @php
                                $outOfStockCount = $product->activeVariants->where('stock', '<=', 0)->count();
                            @endphp
                            @if($outOfStockCount > 0)
                                <p class="text-xs text-slate-500 mt-3 flex items-center gap-1">
                                    <i class="fas fa-info-circle text-slate-600"></i>
                                    {{ $outOfStockCount }} varian sedang habis
                                </p>
                            @endif
                        </div>
                    @endif

                    {{-- Actions --}}
                    <div class="space-y-4 mb-8">
                        <div class="bg-blue-900/20 border border-blue-500/20 rounded-xl p-4 flex items-start space-x-3">
                            <i class="fas fa-info-circle text-blue-400 mt-0.5"></i>
                            <p class="text-sm text-blue-200/80 leading-relaxed">
                                Pembelian dilakukan secara langsung di Koperasi. Silakan kunjungi kami pada jam operasional.
                            </p>
                        </div>

                        <a href="{{ route('home') }}" 
                           wire:navigate
                           class="flex items-center justify-center w-full py-3 px-6 bg-white/5 hover:bg-white/10 text-slate-300 hover:text-white font-medium rounded-xl border border-white/10 transition-all">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Kembali ke Katalog
                        </a>
                    </div>

                    {{-- Description --}}
                    @if($product->description)
                        <div class="border-t border-white/5 pt-6">
                            <h3 class="text-lg font-semibold text-white mb-3">Deskripsi</h3>
                            <div class="prose prose-invert prose-sm max-w-none text-slate-300">
                                <p>{{ $product->description }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Footer Info --}}
        <div class="mt-8 lg:mt-12 grid grid-cols-1 md:grid-cols-3 gap-4 lg:gap-6">
            <!-- Reuse glass card style -->
            <div class="bg-slate-900/40 border border-white/5 rounded-2xl p-5 lg:p-6 text-center">
                <i class="fas fa-clock text-blue-500 text-2xl mb-3"></i>
                <h4 class="text-white font-medium mb-1">Jam Operasional</h4>
                <p class="text-sm text-slate-400">Senin - Kamis, 07:30 - 16:00</p>
            </div>
            <div class="bg-slate-900/40 border border-white/5 rounded-2xl p-6 text-center">
                <i class="fas fa-map-marker-alt text-blue-500 text-2xl mb-3"></i>
                <h4 class="text-white font-medium mb-1">Lokasi</h4>
                <p class="text-sm text-slate-400">Kampus Universitas, Yogyakarta</p>
            </div>
            <div class="bg-slate-900/40 border border-white/5 rounded-2xl p-6 text-center">
                <i class="fas fa-headset text-blue-500 text-2xl mb-3"></i>
                <h4 class="text-white font-medium mb-1">Bantuan</h4>
                <p class="text-sm text-slate-400">Hubungi admin via WhatsApp</p>
            </div>
        </div>
    </div>
</div>