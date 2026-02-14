<div class="space-y-4 sm:space-y-6" wire:init="$refresh">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">Laporan Penjualan</h1>
            <p class="text-xs sm:text-sm text-gray-500 mt-0.5">
                {{ \Carbon\Carbon::parse($dateFrom)->translatedFormat('d M Y') }} -
                {{ \Carbon\Carbon::parse($dateTo)->translatedFormat('d M Y') }}
            </p>
        </div>
        <div class="flex gap-2">
            <x-ui.button variant="secondary" wire:click="exportSales" wire:loading.attr="disabled" size="sm">
                <x-ui.icon name="arrow-down-tray" class="w-4 h-4 mr-1.5" />
                <span wire:loading.remove wire:target="exportSales">Export Transaksi</span>
                <span wire:loading wire:target="exportSales">Mengunduh...</span>
            </x-ui.button>
            <x-ui.button variant="secondary" wire:click="exportSaleItems" wire:loading.attr="disabled" size="sm">
                <x-ui.icon name="arrow-down-tray" class="w-4 h-4 mr-1.5" />
                <span wire:loading.remove wire:target="exportSaleItems">Export Item</span>
                <span wire:loading wire:target="exportSaleItems">Mengunduh...</span>
            </x-ui.button>
        </div>
    </div>

    {{-- Period Selector --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg p-3 sm:p-4 border border-gray-200 dark:border-gray-700">
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <div class="flex flex-wrap gap-2">
                @foreach(['today' => 'Hari Ini', 'week' => 'Minggu', 'month' => 'Bulan'] as $key => $label)
                    <button wire:click="setPeriod('{{ $key }}')"
                        @class([
                            'px-3 py-1.5 text-xs sm:text-sm font-medium rounded-lg transition',
                            'bg-primary-600 text-white' => $period === $key,
                            'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300' => $period !== $key,
                        ])>
                        {{ $label }}
                    </button>
                @endforeach
            </div>
            
            <div class="flex items-center gap-2 sm:ml-auto">
                <input type="date" wire:model.live.debounce.500ms="dateFrom" 
                    class="flex-1 sm:flex-none px-2 py-1.5 text-xs sm:text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <span class="text-gray-400 text-xs">—</span>
                <input type="date" wire:model.live.debounce.500ms="dateTo" 
                    class="flex-1 sm:flex-none px-2 py-1.5 text-xs sm:text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-lg p-3 sm:p-4 text-white">
            <p class="text-emerald-100 text-xs font-medium">Pendapatan</p>
            <p class="text-lg sm:text-xl font-bold mt-1">{{ format_currency($this->reportData->revenue) }}</p>
            <p class="text-emerald-200 text-xs mt-0.5">{{ number_format($this->reportData->total) }} transaksi</p>
        </div>
        
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg p-3 sm:p-4 text-white">
            <p class="text-blue-100 text-xs font-medium">Rata-rata</p>
            <p class="text-lg sm:text-xl font-bold mt-1">{{ format_currency($this->reportData->avg_amount) }}</p>
            <p class="text-blue-200 text-xs mt-0.5">per transaksi</p>
        </div>
        
        <div class="bg-gradient-to-br from-violet-500 to-violet-600 rounded-lg p-3 sm:p-4 text-white">
            <p class="text-violet-100 text-xs font-medium">Terbesar</p>
            <p class="text-lg sm:text-xl font-bold mt-1">{{ format_currency($this->reportData->max_amount) }}</p>
            <p class="text-violet-200 text-xs mt-0.5">nilai tertinggi</p>
        </div>
        
        <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-lg p-3 sm:p-4 text-white">
            <p class="text-amber-100 text-xs font-medium">Transaksi</p>
            <p class="text-lg sm:text-xl font-bold mt-1">{{ number_format($this->reportData->total) }}</p>
            <p class="text-amber-200 text-xs mt-0.5">total</p>
        </div>
    </div>

    {{-- Charts & Payment --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- Revenue Chart --}}
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-sm text-gray-900 dark:text-white mb-3">Grafik Penjualan</h3>
            <div class="h-48 sm:h-56" wire:ignore>
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        {{-- Payment Methods --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-sm text-gray-900 dark:text-white mb-3">Metode Pembayaran</h3>
            <div class="space-y-3">
                @foreach($this->paymentSummary as $method)
                    <div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-gray-700 dark:text-gray-300">{{ $method['name'] }}</span>
                            <span class="font-semibold">{{ $method['count'] }} ({{ $method['percentage'] }}%)</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-{{ $method['color'] }}-500 h-2 rounded-full" style="width: {{ $method['percentage'] }}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5">{{ format_currency($method['amount']) }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Top Products & Hourly --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {{-- Top Products --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-sm text-gray-900 dark:text-white mb-3">Produk Terlaris</h3>
            @if(count($this->topProducts) > 0)
                <div class="space-y-2">
                    @foreach($this->topProducts as $i => $product)
                        <div class="flex items-center gap-2 p-2 rounded hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <span @class([
                                'w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold',
                                'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-400' => $i < 3,
                                'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' => $i >= 3,
                            ])>{{ $i + 1 }}</span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $product->name }}</p>
                                <p class="text-xs text-gray-500">{{ number_format($product->total_qty) }} terjual</p>
                            </div>
                            <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                {{ format_currency($product->total_revenue) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-400 text-center py-8">Belum ada data</p>
            @endif
        </div>

        {{-- Hourly Distribution --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-sm text-gray-900 dark:text-white mb-3">Distribusi Jam</h3>
            <div class="h-36" wire:ignore>
                <canvas id="hourlyChart"></canvas>
            </div>
            @if($this->peakHour)
                <div class="mt-3 p-2 bg-primary-50 dark:bg-primary-900/20 rounded text-center">
                    <p class="text-xs text-gray-600 dark:text-gray-400">
                        Jam Tersibuk: 
                        <span class="font-bold text-primary-600 dark:text-primary-400">
                            {{ str_pad($this->peakHour['hour'], 2, '0', STR_PAD_LEFT) }}:00
                        </span>
                        ({{ $this->peakHour['count'] }} tx)
                    </p>
                </div>
            @endif
        </div>
    </div>

    {{-- Transactions Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="font-semibold text-sm text-gray-900 dark:text-white">Detail Transaksi</h3>
            <span class="text-xs text-gray-500">{{ $sales->total() }} data</span>
        </div>
        
        {{-- Mobile Cards --}}
        <div class="sm:hidden divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($sales as $sale)
                <div class="p-3 space-y-1">
                    <div class="flex justify-between items-start">
                        <div wire:click="showDetail({{ $sale->id }})" class="cursor-pointer flex-1">
                            <span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded">{{ $sale->invoice_number }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-sm text-gray-900 dark:text-white">{{ format_currency($sale->total_amount) }}</span>
                                                        @can('kelola_penjualan')
                            <button wire:click="confirmDelete({{ $sale->id }})" class="p-1 text-gray-400 hover:text-red-600 dark:hover:text-red-400 rounded" title="Hapus">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                            @endcan
                        </div>
                    </div>
                    <div wire:click="showDetail({{ $sale->id }})" class="cursor-pointer">
                        <div class="flex justify-between text-xs text-gray-500">
                            <span>{{ $sale->created_at->format('d/m H:i') }}</span>
                            <span class="px-1.5 py-0.5 rounded text-xs font-medium
                                {{ $sale->payment_method === 'cash' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400' : '' }}
                                {{ $sale->payment_method === 'transfer' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-400' : '' }}
                                {{ $sale->payment_method === 'qris' ? 'bg-violet-100 text-violet-700 dark:bg-violet-900/50 dark:text-violet-400' : '' }}">
                                {{ strtoupper($sale->payment_method) }}
                            </span>
                        </div>
                        <div class="text-xs text-gray-500">{{ $sale->cashier->name ?? '-' }} • {{ $sale->items_count }} item</div>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-gray-400 text-sm">Tidak ada transaksi</div>
            @endforelse
        </div>

        {{-- Desktop Table --}}
        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/50 text-xs text-gray-500 uppercase">
                    <tr>
                        <th class="px-4 py-2.5 text-left">Invoice</th>
                        <th class="px-4 py-2.5 text-left">Tanggal</th>
                        <th class="px-4 py-2.5 text-left">Kasir</th>
                        <th class="px-4 py-2.5 text-center">Item</th>
                        <th class="px-4 py-2.5 text-center">Metode</th>
                        <th class="px-4 py-2.5 text-right">Total</th>
                                                    @can('kelola_penjualan')
                        <th class="px-4 py-2.5 text-center w-16">Aksi</th>
                        @endcan
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($sales as $sale)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30">
                            <td wire:click="showDetail({{ $sale->id }})" class="px-4 py-2.5 cursor-pointer">
                                <span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded">{{ $sale->invoice_number }}</span>
                            </td>
                            <td wire:click="showDetail({{ $sale->id }})" class="px-4 py-2.5 text-gray-600 dark:text-gray-400 text-xs cursor-pointer">
                                {{ $sale->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td wire:click="showDetail({{ $sale->id }})" class="px-4 py-2.5 text-gray-900 dark:text-white cursor-pointer">{{ $sale->cashier->name ?? '-' }}</td>
                            <td wire:click="showDetail({{ $sale->id }})" class="px-4 py-2.5 text-center text-xs cursor-pointer">{{ $sale->items_count }}</td>
                            <td wire:click="showDetail({{ $sale->id }})" class="px-4 py-2.5 text-center cursor-pointer">
                                <span class="px-1.5 py-0.5 rounded text-xs font-medium
                                    {{ $sale->payment_method === 'cash' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400' : '' }}
                                    {{ $sale->payment_method === 'transfer' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-400' : '' }}
                                    {{ $sale->payment_method === 'qris' ? 'bg-violet-100 text-violet-700 dark:bg-violet-900/50 dark:text-violet-400' : '' }}">
                                    {{ strtoupper($sale->payment_method) }}
                                </span>
                            </td>
                            <td wire:click="showDetail({{ $sale->id }})" class="px-4 py-2.5 text-right font-semibold text-gray-900 dark:text-white cursor-pointer">
                                {{ format_currency($sale->total_amount) }}
                            </td>
                                                        @can('kelola_penjualan')
                            <td class="px-4 py-2.5 text-center">
                                <button wire:click="confirmDelete({{ $sale->id }})" class="p-1.5 text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded transition" title="Hapus transaksi">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </td>
                            @endcan
                        </tr>
                    @empty
                        <tr>
                            <td colspan="                            @can('kelola_penjualan')7@else 6 @endcan" class="px-4 py-12 text-center text-gray-400 text-sm">Tidak ada transaksi</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($sales->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $sales->links() }}
            </div>
        @endif
    </div>

    {{-- Loading --}}
    <div wire:loading.delay class="fixed inset-0 bg-black/20 backdrop-blur-sm flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg px-4 py-3 shadow-lg flex items-center gap-2">
            <svg class="animate-spin h-4 w-4 text-primary-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span class="text-sm text-gray-700 dark:text-gray-300">Memuat...</span>
        </div>
    </div>

    {{-- Detail Modal --}}
    @if($selectedSaleId && $this->selectedSale)
        <div class="fixed inset-0 z-50 overflow-y-auto" wire:keydown.escape.window="closeDetail">
            <div class="flex min-h-full items-end sm:items-center justify-center p-4">
                {{-- Backdrop --}}
                <div wire:click="closeDetail" class="fixed inset-0 bg-black/50 transition-opacity"></div>
                
                {{-- Modal --}}
                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md sm:max-w-lg transform transition-all">
                    {{-- Header --}}
                    <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Detail Transaksi</h3>
                            <p class="text-xs text-gray-500 font-mono">{{ $this->selectedSale->invoice_number }}</p>
                        </div>
                        <button wire:click="closeDetail" class="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    
                    {{-- Content --}}
                    <div class="p-4 space-y-4 max-h-[60vh] overflow-y-auto">
                        {{-- Info --}}
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <p class="text-xs text-gray-500">Tanggal</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $this->selectedSale->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Kasir</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $this->selectedSale->cashier->name ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Metode</p>
                                <span class="inline-block px-2 py-0.5 rounded text-xs font-medium
                                    {{ $this->selectedSale->payment_method === 'cash' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400' : '' }}
                                    {{ $this->selectedSale->payment_method === 'transfer' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-400' : '' }}
                                    {{ $this->selectedSale->payment_method === 'qris' ? 'bg-violet-100 text-violet-700 dark:bg-violet-900/50 dark:text-violet-400' : '' }}">
                                    {{ strtoupper($this->selectedSale->payment_method) }}
                                </span>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Item</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $this->selectedSale->items->count() }} produk</p>
                            </div>
                        </div>

                        {{-- Items --}}
                        <div>
                            <p class="text-xs text-gray-500 mb-2">Daftar Item</p>
                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($this->selectedSale->items as $item)
                                    <div class="p-3 flex justify-between items-start gap-2">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                {{ $item->product->name ?? $item->product_name }}
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                {{ $item->quantity }} × {{ format_currency($item->price) }}
                                            </p>
                                        </div>
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white shrink-0">
                                            {{ format_currency($item->subtotal) }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Payment Summary --}}
                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-3 space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">Total</span>
                                <span class="font-bold text-gray-900 dark:text-white">{{ format_currency($this->selectedSale->total_amount) }}</span>
                            </div>
                            @if($this->selectedSale->payment_amount)
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">Bayar</span>
                                    <span class="text-gray-900 dark:text-white">{{ format_currency($this->selectedSale->payment_amount) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">Kembalian</span>
                                    <span class="text-gray-900 dark:text-white">{{ format_currency($this->selectedSale->change_amount) }}</span>
                                </div>
                            @endif
                        </div>

                        @if($this->selectedSale->notes)
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Catatan</p>
                                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $this->selectedSale->notes }}</p>
                            </div>
                        @endif
                    </div>

                    {{-- Footer --}}
                    <div class="p-4 border-t border-gray-200 dark:border-gray-700 flex gap-2">
                        <button wire:click="closeDetail" class="flex-1 px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg transition">
                            Tutup
                        </button>
                                                    @can('kelola_penjualan')
                        <button wire:click="confirmDelete({{ $this->selectedSale->id }})" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Hapus
                        </button>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if($showDeleteModal)
        <div class="fixed inset-0 z-[60] overflow-y-auto" wire:keydown.escape.window="cancelDelete">
            <div class="flex min-h-full items-center justify-center p-4">
                {{-- Backdrop --}}
                <div wire:click="cancelDelete" class="fixed inset-0 bg-black/50 transition-opacity"></div>
                
                {{-- Modal --}}
                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-sm transform transition-all">
                    <div class="p-6 text-center">
                        {{-- Icon --}}
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/30 mb-4">
                            <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Hapus Transaksi?</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                            Transaksi akan dihapus dan stok produk akan dikembalikan. Tindakan ini tidak dapat dibatalkan.
                        </p>
                        
                        <div class="flex gap-3">
                            <button wire:click="cancelDelete" class="flex-1 px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg transition">
                                Batal
                            </button>
                            <button wire:click="deleteSale" class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition">
                                <span wire:loading.remove wire:target="deleteSale">Ya, Hapus</span>
                                <span wire:loading wire:target="deleteSale" class="flex items-center justify-center gap-2">
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    Menghapus...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Chart Data --}}
    <div id="chartData" class="hidden"
        data-labels='@json($this->chartData["labels"])'
        data-revenue='@json($this->chartData["revenue"])'
        data-hourly='@json(array_values($this->hourlySales))'>
    </div>
</div>

@script
<script>
(function() {
    let charts = {};
    const colors = { emerald: '#10b981', blue: '#3b82f6' };

    function fmt(v) {
        return v >= 1e6 ? (v/1e6).toFixed(1)+'jt' : v >= 1e3 ? (v/1e3).toFixed(0)+'rb' : v;
    }

    function init() {
        if (typeof Chart === 'undefined') return;
        
        const el = document.getElementById('chartData');
        if (!el) return;

        const d = {
            labels: JSON.parse(el.dataset.labels || '[]'),
            revenue: JSON.parse(el.dataset.revenue || '[]'),
            hourly: JSON.parse(el.dataset.hourly || '[]')
        };

        Object.values(charts).forEach(c => c?.destroy?.());
        charts = {};

        const rc = document.getElementById('revenueChart');
        if (rc) {
            charts.r = new Chart(rc, {
                type: 'line',
                data: {
                    labels: d.labels,
                    datasets: [{
                        data: d.revenue,
                        borderColor: colors.emerald,
                        backgroundColor: 'rgba(16,185,129,0.1)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: d.labels.length > 20 ? 0 : 3,
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 300 },
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, ticks: { maxTicksLimit: 10, font: { size: 10 } } },
                        y: { beginAtZero: true, ticks: { callback: v => fmt(v), font: { size: 10 } } }
                    }
                }
            });
        }

        const hc = document.getElementById('hourlyChart');
        if (hc) {
            const hrs = Array.from({length:24}, (_,i) => i+'h');
            charts.h = new Chart(hc, {
                type: 'bar',
                data: {
                    labels: hrs,
                    datasets: [{
                        data: d.hourly,
                        backgroundColor: 'rgba(59,130,246,0.6)',
                        borderRadius: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 300 },
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, ticks: { callback: (_,i) => i%6===0 ? i+'h' : '', font: { size: 9 } } },
                        y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 9 } } }
                    }
                }
            });
        }
    }

    init();
    Livewire.hook('morph.updated', () => setTimeout(init, 50));
})();
</script>
@endscript
