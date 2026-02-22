<div class="p-6">
    
    
    

    <x-layout.page-header 
        title="Manajemen Produk"
        description="Kelola produk untuk penjualan"
    >
        <x-slot:actions>
            <x-ui.button 
                variant="primary" 
                icon="plus"
                href="{{ route('admin.products.create') }}"
            >
                Tambah Produk
            </x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <x-layout.grid cols="4" class="mb-6">
        <x-layout.stat-card 
            label="Total Produk"
            :value="$stats['total']"
            icon="cube"
            icon-color="bg-primary-100"
            icon-text-color="text-primary-600"
        />

        <x-layout.stat-card 
            label="Produk Aktif"
            :value="$stats['active']"
            icon="check-circle"
            icon-color="bg-success-100"
            icon-text-color="text-success-600"
        />

        <x-layout.stat-card 
            label="Stok Menipis"
            :value="$stats['low_stock']"
            icon="exclamation-triangle"
            icon-color="bg-warning-100"
            icon-text-color="text-warning-600"
        />

        <x-layout.stat-card 
            label="Stok Habis"
            :value="$stats['out_of_stock']"
            icon="x-circle"
            icon-color="bg-danger-100"
            icon-text-color="text-danger-600"
        />
    </x-layout.grid>

    @if($stats['low_variant_stock'] > 0)
    <x-ui.alert variant="warning" class="mb-6">
        <div class="flex items-center">
            <x-ui.icon name="exclamation-triangle" class="w-5 h-5 mr-2" />
            <span>{{ $stats['low_variant_stock'] }} produk memiliki varian dengan stok menipis.</span>
            <button 
                wire:click="$set('stockFilter', 'low_variant')" 
                class="ml-2 underline hover:no-underline"
            >
                Lihat
            </button>
        </div>
    </x-ui.alert>
    @endif

    <x-ui.card class="mb-6">
        <x-layout.grid cols="4">
            <x-ui.input 
                label="Cari Produk"
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Nama, SKU, Kategori..."
            />

            <x-ui.select 
                label="Status"
                wire:model.live="statusFilter"
                placeholder="Semua Status"
                :options="[
                    'active' => 'Aktif',
                    'inactive' => 'Nonaktif'
                ]"
            />

            <x-ui.select 
                label="Kategori"
                wire:model.live="categoryFilter"
                placeholder="Semua Kategori"
                :options="collect($categories)->mapWithKeys(fn($cat) => [$cat => $cat])->toArray()"
            />

            <x-ui.select 
                label="Stok"
                wire:model.live="stockFilter"
                placeholder="Semua Stok"
                :options="[
                    'available' => 'Tersedia',
                    'low' => 'Stok Menipis',
                    'out' => 'Stok Habis',
                    'low_variant' => 'Varian Stok Menipis'
                ]"
            />
        </x-layout.grid>
    </x-ui.card>

    <x-data.table :headers="['Produk', 'Kategori', 'Harga', 'Stok', 'Status', 'Aksi']">
        @forelse ($products as $product)
            <x-data.table-row>
                <x-data.table-cell>
                    <div class="font-medium text-gray-900">{{ $product->name }}</div>
                    @if($product->has_variants)
                        <div class="text-xs text-primary-600 flex items-center gap-1">
                            <x-ui.icon name="squares-2x2" class="w-3 h-3" />
                            <span>{{ $product->active_variants_count ?? $product->variant_count }} varian</span>
                            @php
                                $lowStockVariants = $product->activeVariants->filter(fn($v) => $v->stock <= $v->min_stock);
                            @endphp
                            @if($lowStockVariants->count() > 0)
                                <span class="text-warning-600 ml-1" title="Varian dengan stok menipis">
                                    ({{ $lowStockVariants->count() }} stok rendah)
                                </span>
                            @endif
                        </div>
                        {{-- Show low stock variant details when filter is active --}}
                        @if($stockFilter === 'low_variant' && $lowStockVariants->count() > 0)
                            <div class="mt-2 p-2 bg-warning-50 rounded-md border border-warning-200">
                                <div class="text-xs font-medium text-warning-800 mb-1">Varian Stok Menipis:</div>
                                <ul class="text-xs text-warning-700 space-y-1">
                                    @foreach($lowStockVariants->take(5) as $variant)
                                        <li class="flex justify-between">
                                            <span>{{ $variant->variant_name }}</span>
                                            <span class="font-medium">Stok: {{ $variant->stock }}/{{ $variant->min_stock }}</span>
                                        </li>
                                    @endforeach
                                    @if($lowStockVariants->count() > 5)
                                        <li class="text-warning-600 italic">+{{ $lowStockVariants->count() - 5 }} varian lainnya...</li>
                                    @endif
                                </ul>
                            </div>
                        @endif
                    @elseif($product->description)
                        <div class="text-sm text-gray-500 truncate max-w-xs">{{ Str::limit($product->description, 50) }}</div>
                    @endif
                </x-data.table-cell>
                <x-data.table-cell>
                    @if($product->category)
                        <x-ui.badge variant="info" size="sm">{{ $product->category }}</x-ui.badge>
                    @else
                        <span class="text-sm text-gray-400">-</span>
                    @endif
                </x-data.table-cell>
                <x-data.table-cell>
                    @if($product->has_variants)
                        <span class="text-sm text-gray-900">{{ $product->display_price }}</span>
                    @else
                        <span class="font-semibold text-gray-900">Rp {{ number_format($product->price, 0, ',', '.') }}</span>
                    @endif
                </x-data.table-cell>
                <x-data.table-cell>
                    @php 
                        // Use eager loaded value if available, otherwise use accessor
                        $totalStock = $product->has_variants 
                            ? ($product->variants_total_stock ?? $product->total_stock) 
                            : $product->stock;
                    @endphp
                    <div class="flex flex-col">
                        @if($totalStock > $product->min_stock)
                            <x-ui.badge variant="success">{{ $totalStock }}</x-ui.badge>
                        @elseif($totalStock > 0)
                            <x-ui.badge variant="warning">{{ $totalStock }}</x-ui.badge>
                        @else
                            <x-ui.badge variant="danger">{{ $totalStock }}</x-ui.badge>
                        @endif
                        @if($product->has_variants)
                            <span class="text-xs text-gray-500 mt-1">Total dari {{ $product->active_variants_count ?? $product->variant_count }} varian</span>
                        @endif
                    </div>
                </x-data.table-cell>
                <x-data.table-cell>
                    <x-ui.button 
                        :variant="$product->status === 'active' ? 'success' : 'secondary'"
                        size="sm"
                        wire:click="toggleStatus({{ $product->id }})"
                    >
                        {{ $product->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                    </x-ui.button>
                </x-data.table-cell>
                <x-data.table-cell>
                    <div class="flex items-center justify-end space-x-2">
                        <x-ui.button 
                            variant="ghost" 
                            size="sm"
                            icon="pencil"
                            href="{{ route('admin.products.edit', $product->id) }}"
                        >
                            Edit
                        </x-ui.button>
                        <x-ui.button 
                            variant="ghost" 
                            size="sm"
                            wire:click="delete({{ $product->id }})"
                            wire:confirm="Yakin ingin menghapus produk ini?"
                        >
                            Hapus
                        </x-ui.button>
                    </div>
                </x-data.table-cell>
            </x-data.table-row>
        @empty
            <tr>
                <td colspan="6">
                    <x-layout.empty-state 
                        icon="cube"
                        title="Tidak ada produk ditemukan"
                        description="Coba ubah filter atau tambah produk baru"
                    >
                        <x-slot:action>
                            <x-ui.button 
                                variant="primary"
                                icon="plus"
                                href="{{ route('admin.products.create') }}"
                            >
                                Tambah Produk
                            </x-ui.button>
                        </x-slot:action>
                    </x-layout.empty-state>
                </td>
            </tr>
        @endforelse
    </x-data.table>

    <div class="mt-4">
        {{ $products->links() }}
    </div>
</div>
