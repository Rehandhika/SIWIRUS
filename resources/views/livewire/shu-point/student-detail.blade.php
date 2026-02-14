<div class="space-y-6">
    <x-layout.page-header
        title="Poin SHU - Detail Mahasiswa"
        description="Riwayat poin masuk/keluar dan aksi pencairan/penyesuaian"
    >
        <x-slot:actions>
            <a href="{{ route('admin.poin-shu.monitoring') }}" class="px-4 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 font-semibold rounded-xl">
                Kembali
            </a>
        </x-slot:actions>
    </x-layout.page-header>

    <x-ui.card>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-1 space-y-2">
                <div class="text-sm text-gray-500">NIM</div>
                <div class="font-mono font-bold text-gray-900 dark:text-white text-lg">{{ $student->nim }}</div>
                <div class="text-sm text-gray-500">Nama</div>
                <div class="font-semibold text-gray-900 dark:text-white">{{ $student->full_name }}</div>
                <div class="mt-3 p-3 bg-primary-50 dark:bg-primary-900/20 rounded-xl">
                    <div class="text-sm text-primary-700 dark:text-primary-300">Saldo Poin</div>
                    <div class="text-2xl font-bold text-primary-700 dark:text-primary-300">{{ number_format($student->points_balance, 0, ',', '.') }}</div>
                </div>
            </div>

            <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                @can('kelola_poin_shu')
                    <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-xl">
                        <div class="font-bold text-gray-900 dark:text-white mb-3">Pencairan (Redeem)</div>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Poin Dicairkan</label>
                                <input type="number" min="1" wire:model.defer="redeemPoints" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl">
                                @error('redeemPoints') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Nominal Pencairan (Opsional)</label>
                                <input type="number" min="0" wire:model.defer="redeemCashAmount" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl">
                                @error('redeemCashAmount') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Catatan (Opsional)</label>
                                <input type="text" wire:model.defer="redeemNotes" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl">
                                @error('redeemNotes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <button type="button" wire:click="redeem" class="w-full px-4 py-2.5 bg-red-600 text-white font-semibold rounded-xl">
                                Simpan Pencairan
                            </button>
                        </div>
                    </div>
                @endcan

                @can('kelola_poin_shu')
                    <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-xl">
                        <div class="font-bold text-gray-900 dark:text-white mb-3">Penyesuaian (Adjust)</div>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Perubahan Poin</label>
                                <input type="number" wire:model.defer="adjustPoints" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl" placeholder="Contoh: 10 atau -10">
                                @error('adjustPoints') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Catatan (Opsional)</label>
                                <input type="text" wire:model.defer="adjustNotes" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl">
                                @error('adjustNotes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <button type="button" wire:click="adjust" class="w-full px-4 py-2.5 bg-gray-900 dark:bg-gray-200 dark:text-gray-900 text-white font-semibold rounded-xl">
                                Simpan Penyesuaian
                            </button>
                        </div>
                    </div>
                @endcan
            </div>
        </div>
    </x-ui.card>

    <x-ui.card>
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 flex-1">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Tipe</label>
                    <select wire:model.live="typeFilter" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl">
                        <option value="">Semua</option>
                        <option value="earn">Masuk (Pembelian)</option>
                        <option value="redeem">Keluar (Pencairan)</option>
                        <option value="adjust">Penyesuaian</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Dari</label>
                    <input type="date" wire:model.live="dateFrom" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Sampai</label>
                    <input type="date" wire:model.live="dateTo" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Cari</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <x-ui.icon name="magnifying-glass" class="h-4 w-4 text-gray-400" />
                        </div>
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Invoice / catatan" class="w-full pl-9 pr-4 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl">
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Tanggal</th>
                        <th class="px-4 py-3 text-left font-semibold">Tipe</th>
                        <th class="px-4 py-3 text-right font-semibold">Poin</th>
                        <th class="px-4 py-3 text-right font-semibold">Nominal</th>
                        <th class="px-4 py-3 text-right font-semibold">Pencairan</th>
                        <th class="px-4 py-3 text-left font-semibold">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($this->transactions as $trx)
                        @php
                            $typeLabel = match($trx->type) {
                                'earn' => 'Masuk',
                                'redeem' => 'Keluar',
                                default => 'Penyesuaian',
                            };
                        @endphp
                        <tr class="text-gray-900 dark:text-gray-100">
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $trx->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-lg text-xs font-bold
                                    {{ $trx->type === 'earn' ? 'bg-green-100 text-green-700' : ($trx->type === 'redeem' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700') }}">
                                    {{ $typeLabel }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold {{ $trx->points >= 0 ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400' }}">
                                {{ number_format($trx->points, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right">{{ $trx->amount ? number_format($trx->amount, 0, ',', '.') : '-' }}</td>
                            <td class="px-4 py-3 text-right">{{ $trx->cash_amount ? number_format($trx->cash_amount, 0, ',', '.') : '-' }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                @if($trx->sale)
                                    <span class="text-xs text-gray-500 block">Inv: {{ $trx->sale->invoice_number }}</span>
                                @endif
                                {{ $trx->notes ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">Tidak ada transaksi</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $this->transactions->links() }}
        </div>
    </x-ui.card>
</div>
