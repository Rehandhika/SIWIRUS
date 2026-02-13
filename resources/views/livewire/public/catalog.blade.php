<div class="min-h-screen">
    {{-- Banner Carousel Section --}}
    @if($banners->count() > 0)
        <div class="w-full relative group">
            <div class="absolute inset-0 bg-gradient-to-b from-slate-950/0 to-slate-950 pointer-events-none z-10"></div>
            <x-ui.banner-carousel :banners="$banners" />
        </div>
    @endif

    {{-- Main Content --}}
    <div class="max-w-7xl mx-auto px-4 py-12 relative z-20">
        
        {{-- Header Section --}}
        <div class="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-white tracking-tight mb-2">Katalog Produk</h1>
                <p class="text-slate-400">Temukan kebutuhan harianmu dengan harga terbaik.</p>
            </div>
        </div>

        {{-- Search & Filter Bar (Glass Panel) --}}
        <div class="relative z-40 bg-slate-900/60 backdrop-blur-md border border-white/10 rounded-2xl p-4 mb-10 shadow-xl">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                {{-- Search Input --}}
                <div class="md:col-span-8 relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fas fa-search text-slate-500"></i>
                    </div>
                    <input 
                        type="text" 
                        wire:model.live="search"
                        placeholder="Cari produk (nama, SKU)..."
                        class="w-full pl-11 pr-4 py-3 bg-slate-950/50 border border-white/10 rounded-xl focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 text-white placeholder-slate-600 transition-all"
                    >
                </div>

                {{-- Category Filter (Custom Alpine Dropdown) --}}
                <div class="md:col-span-4 relative" x-data="{ open: false }">
                    <button 
                        @click="open = !open" 
                        @click.away="open = false"
                        type="button"
                        class="w-full flex items-center justify-between pl-3 pr-4 py-2.5 bg-slate-950/50 border border-white/10 rounded-xl focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 text-left transition-all hover:bg-slate-900/80 group h-[52px]"
                    >
                        <div class="flex items-center gap-3 overflow-hidden">
                            <div class="w-8 h-8 rounded-lg bg-blue-500/10 flex items-center justify-center text-blue-400 group-hover:bg-blue-500 group-hover:text-white transition-colors shrink-0">
                                <i class="fas fa-filter text-xs"></i>
                            </div>
                            <span class="text-slate-300 font-medium truncate block">
                                {{ $category ?: 'Semua Kategori' }}
                            </span>
                        </div>
                        <i class="fas fa-chevron-down text-slate-500 text-xs transition-transform duration-300 shrink-0 ml-2" :class="{ 'rotate-180': open }"></i>
                    </button>

                    {{-- Dropdown Menu --}}
                    <div 
                        x-show="open" 
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                        x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                        class="absolute top-full mt-2 right-0 left-0 bg-slate-900/95 backdrop-blur-xl border border-white/10 rounded-xl shadow-2xl z-[60] overflow-hidden ring-1 ring-white/5"
                        style="display: none;"
                    >
                        <div class="p-1 max-h-80 overflow-y-auto custom-scrollbar">
                            <button 
                                @click="$wire.set('category', ''); open = false"
                                type="button"
                                class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm transition-colors {{ $category == '' ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}"
                            >
                                <span>Semua Kategori</span>
                                @if($category == '') <i class="fas fa-check text-xs"></i> @endif
                            </button>
                            
                            @foreach($categories as $cat)
                                <button 
                                    @click="$wire.set('category', '{{ $cat }}'); open = false"
                                    type="button"
                                    class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm transition-colors {{ $category == $cat ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}"
                                >
                                    <span>{{ $cat }}</span>
                                    @if($category == $cat) <i class="fas fa-check text-xs"></i> @endif
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Products Grid --}}
        @if($products->count() > 0)
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                @foreach($products as $product)
                    <div wire:key="product-{{ $product->id }}" class="group relative bg-slate-900/40 border border-white/5 rounded-2xl overflow-hidden backdrop-blur-sm transition-all duration-300 hover:bg-slate-800/60 hover:border-blue-500/30 hover:shadow-[0_0_20px_rgba(99,102,241,0.15)] hover:-translate-y-1">
                        
                        {{-- Image Area --}}
                        <div class="aspect-square relative overflow-hidden bg-slate-800">
                            @if($product->hasImage())
                                <img 
                                    src="{{ $product->image_medium_url }}" 
                                    alt="{{ $product->name }}" 
                                    class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110 opacity-90 group-hover:opacity-100" 
                                    loading="lazy"
                                    decoding="async"
                                    onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'w-full h-full flex items-center justify-center text-slate-600 bg-slate-800/50\'><i class=\'fas fa-cube text-4xl opacity-50\'></i></div>';"
                                >
                            @else
                                <div class="w-full h-full flex items-center justify-center text-slate-600 bg-slate-800/50">
                                    <i class="fas fa-cube text-4xl opacity-50"></i>
                                </div>
                            @endif

                            {{-- Badges --}}
                            <div class="absolute top-3 right-3 flex flex-col gap-2 items-end">
                                @if($product->is_featured)
                                    <span class="bg-yellow-500/20 text-yellow-300 backdrop-blur-md border border-yellow-500/30 px-2 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider shadow-lg">
                                        Featured
                                    </span>
                                @endif
                                @if($product->has_variants && $product->variant_count > 0)
                                    <span class="bg-blue-500/20 text-blue-300 backdrop-blur-md border border-blue-500/30 px-2 py-1 rounded-lg text-[10px] font-bold shadow-lg flex items-center gap-1">
                                        <i class="fas fa-layer-group text-[8px]"></i>
                                        {{ $product->variant_count }}
                                    </span>
                                @endif
                            </div>

                            {{-- Stock Status Overlay --}}
                            <div class="absolute bottom-3 left-3">
                                @php
                                    $totalStock = $product->total_stock;
                                    $isOutOfStock = $totalStock <= 0;
                                    $isLowStock = !$isOutOfStock && $totalStock <= $product->min_stock;
                                @endphp
                                @if($isOutOfStock)
                                    <span class="bg-red-500/90 text-white px-2 py-1 rounded-md text-xs font-medium shadow-sm">Habis</span>
                                @elseif($isLowStock)
                                    <span class="bg-orange-500/90 text-white px-2 py-1 rounded-md text-xs font-medium shadow-sm">Sisa {{ $totalStock }}</span>
                                @endif
                            </div>
                        </div>

                        {{-- Content --}}
                        <div class="p-5">
                            @if($product->category)
                                <p class="text-[10px] text-blue-400 uppercase tracking-widest font-semibold mb-2">{{ $product->category }}</p>
                            @endif
                            
                            <h3 class="text-sm md:text-base font-medium text-slate-100 mb-1 line-clamp-2 min-h-[2.5rem] group-hover:text-blue-300 transition-colors">
                                {{ $product->name }}
                            </h3>

                            {{-- Stock Info with Variant Badge --}}
                            <div class="flex items-center flex-wrap gap-2 mb-3 text-xs">
                                @if($product->has_variants)
                                    {{-- Variant Count Badge --}}
                                    <span class="inline-flex items-center gap-1 text-blue-300 text-[10px] px-2 py-0.5 bg-blue-500/15 border border-blue-500/30 rounded-full font-medium">
                                        <i class="fas fa-layer-group text-[8px]"></i>
                                        {{ $product->variant_count }} varian
                                    </span>
                                    {{-- Total Stock --}}
                                    <span class="text-slate-500">
                                        Stok: <span class="text-slate-300 font-medium">{{ $product->total_stock }}</span>
                                    </span>
                                @else
                                    <span class="text-slate-500">
                                        Stok: <span class="text-slate-300 font-medium">{{ $product->stock }}</span>
                                    </span>
                                @endif
                            </div>
                            
                            <div class="flex items-end justify-between">
                                <div class="flex flex-col">
                                    @if($product->has_variants)
                                        @php
                                            $priceRange = $product->price_range;
                                            $hasRange = $priceRange['min'] !== $priceRange['max'];
                                        @endphp
                                        <span class="text-xs text-slate-500 mb-1">
                                            {{ $hasRange ? 'Mulai dari' : 'Harga' }}
                                        </span>
                                        <span class="text-lg font-bold text-transparent bg-clip-text bg-gradient-to-r from-white to-slate-400 group-hover:from-blue-400 group-hover:to-cyan-400 transition-all duration-300">
                                            Rp{{ number_format($priceRange['min'], 0, ',', '.') }}
                                        </span>
                                        @if($hasRange)
                                            <span class="text-[10px] text-slate-500 mt-0.5">
                                                s/d Rp{{ number_format($priceRange['max'], 0, ',', '.') }}
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-xs text-slate-500 mb-1">Harga</span>
                                        <span class="text-lg font-bold text-transparent bg-clip-text bg-gradient-to-r from-white to-slate-400 group-hover:from-blue-400 group-hover:to-cyan-400 transition-all duration-300">
                                            {{ $product->display_price }}
                                        </span>
                                    @endif
                                </div>
                                <a href="{{ route('public.products.show', $product->slug) }}" 
                                   wire:navigate.hover
                                   class="w-8 h-8 flex items-center justify-center rounded-full bg-white/5 hover:bg-blue-600 text-slate-400 hover:text-white transition-all border border-white/10">
                                    <i class="fas fa-arrow-right text-xs"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            <div class="mt-8 px-4 py-3 bg-slate-900/50 rounded-xl border border-white/5">
                {{ $products->links() }}
            </div>
        @else
            <div class="bg-slate-900/40 rounded-3xl border border-dashed border-slate-700 p-20 text-center backdrop-blur-sm">
                <div class="w-20 h-20 bg-slate-800/50 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-search text-3xl text-slate-600"></i>
                </div>
                <h3 class="text-xl font-semibold text-white mb-2">Tidak Ada Produk</h3>
                <p class="text-slate-500">Coba ubah kata kunci pencarian atau kategori.</p>
            </div>
        @endif
    </div>
</div>