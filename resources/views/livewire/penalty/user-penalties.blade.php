<div class="space-y-6">
    <!-- Page Header -->
    <x-layout.page-header 
        title="Penalti Saya"
        description="Lihat daftar penalti dan ajukan banding jika diperlukan">
        <x-slot:actions>
            <div class="flex items-center space-x-4">
                <x-ui.badge 
                    :variant="$stats['total_points'] >= 50 ? 'danger' : ($stats['total_points'] >= 20 ? 'warning' : 'info')" 
                    size="lg">
                    Total Poin: <strong>{{ $stats['total_points'] }}</strong>
                </x-ui.badge>
            </div>
        </x-slot:actions>
    </x-layout.page-header>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-ui.card padding="true">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Aktif</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['active_count'] }}</p>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card padding="true">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Dalam Banding</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['appealed_count'] }}</p>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card padding="true">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Dibatalkan</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['dismissed_count'] }}</p>
                </div>
            </div>
        </x-ui.card>
    </div>

    <!-- Threshold Warning -->
    @if($stats['total_points'] >= 50)
        <x-ui.alert variant="danger" :icon="true">
            <strong>Peringatan Kritis!</strong> Total poin penalti Anda telah mencapai {{ $stats['total_points'] }} poin (batas kritis: 50 poin). Harap segera hubungi administrator.
        </x-ui.alert>
    @elseif($stats['total_points'] >= 20)
        <x-ui.alert variant="warning" :icon="true">
            <strong>Peringatan!</strong> Total poin penalti Anda: {{ $stats['total_points'] }} poin. Batas kritis: 50 poin. Harap perhatikan kedisiplinan Anda.
        </x-ui.alert>
    @endif

    <!-- Penalties Table -->
    <x-ui.card padding="false">
        <x-data.table :headers="['Tanggal', 'Jenis Penalti', 'Poin', 'Deskripsi', 'Status', 'Aksi']">
            @forelse($penalties as $penalty)
                <x-data.table-row>
                    <x-data.table-cell>{{ $penalty->date->format('d/m/Y') }}</x-data.table-cell>
                    <x-data.table-cell>
                        <div class="font-medium">{{ $penalty->penaltyType->name }}</div>
                    </x-data.table-cell>
                    <x-data.table-cell>
                        <span class="font-semibold text-red-600">{{ $penalty->points }}</span>
                    </x-data.table-cell>
                    <x-data.table-cell>
                        <div class="max-w-xs truncate">{{ $penalty->description }}</div>
                    </x-data.table-cell>
                    <x-data.table-cell>
                        @if($penalty->status === 'active')
                            <x-ui.badge variant="danger">Aktif</x-ui.badge>
                        @elseif($penalty->status === 'appealed')
                            <x-ui.badge variant="warning">Dalam Banding</x-ui.badge>
                        @elseif($penalty->status === 'dismissed')
                            <x-ui.badge variant="success">Dibatalkan</x-ui.badge>
                        @else
                            <x-ui.badge variant="secondary">{{ ucfirst($penalty->status) }}</x-ui.badge>
                        @endif
                    </x-data.table-cell>
                    <x-data.table-cell>
                        @if($penalty->status === 'active')
                            <x-ui.button 
                                variant="ghost" 
                                size="sm"
                                wire:click="openAppealModal({{ $penalty->id }})">
                                Ajukan Banding
                            </x-ui.button>
                        @elseif($penalty->status === 'appealed')
                            <span class="text-sm text-gray-500">Menunggu Review</span>
                        @else
                            <span class="text-sm text-gray-500">-</span>
                        @endif
                    </x-data.table-cell>
                </x-data.table-row>
            @empty
                <x-data.table-row>
                    <x-data.table-cell colspan="6">
                        <x-layout.empty-state 
                            icon="check-circle"
                            title="Tidak ada penalti"
                            description="Anda tidak memiliki penalti saat ini. Pertahankan kedisiplinan Anda!" />
                    </x-data.table-cell>
                </x-data.table-row>
            @endforelse
        </x-data.table>
    </x-ui.card>

    <x-data.pagination :paginator="$penalties" />

    <!-- Appeal Modal -->
    @if($showAppealModal && $selectedPenalty)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data x-on:keydown.escape.window="$wire.set('showAppealModal', false)">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" wire:click="$set('showAppealModal', false)"></div>
            
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-xl bg-white dark:bg-gray-800 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-2xl">
                    <!-- Header -->
                    <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex items-center justify-between bg-gray-50 dark:bg-gray-800/50">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Ajukan Banding Penalti</h3>
                        <button wire:click="$set('showAppealModal', false)" class="text-gray-400 hover:text-gray-500 transition-colors">
                            <x-ui.icon name="x" class="w-6 h-6" />
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="px-6 py-4 space-y-4">
                        <div class="bg-primary-50 dark:bg-primary-900/20 rounded-lg p-4 border border-primary-100 dark:border-primary-800/30">
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <label class="text-xs font-semibold text-primary-700 dark:text-primary-400 uppercase tracking-wider">Jenis Penalti</label>
                                    <div class="mt-1 font-bold text-gray-900 dark:text-white">{{ $selectedPenalty->penaltyType->name }}</div>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-primary-700 dark:text-primary-400 uppercase tracking-wider">Poin</label>
                                    <div class="mt-1 font-extrabold text-red-600 dark:text-red-400">{{ $selectedPenalty->points }}</div>
                                </div>
                                <div class="col-span-2">
                                    <label class="text-xs font-semibold text-primary-700 dark:text-primary-400 uppercase tracking-wider">Tanggal Kejadian</label>
                                    <div class="mt-1 text-gray-900 dark:text-gray-200 font-medium">{{ $selectedPenalty->date->format('d/m/Y') }}</div>
                                </div>
                                <div class="col-span-2">
                                    <label class="text-xs font-semibold text-primary-700 dark:text-primary-400 uppercase tracking-wider">Deskripsi Sistem</label>
                                    <div class="mt-1 text-gray-700 dark:text-gray-300 leading-relaxed italic">"{{ $selectedPenalty->description }}"</div>
                                </div>
                            </div>
                        </div>

                        <x-ui.alert variant="info" :icon="true">
                            Jelaskan secara detail alasan Anda mengajukan banding. Admin akan meninjau laporan ini dalam waktu 1-3 hari kerja.
                        </x-ui.alert>

                        <x-ui.textarea 
                            name="appealReason"
                            label="Alasan Banding Anda"
                            wire:model="appealReason"
                            rows="4"
                            placeholder="Contoh: Saya terlambat karena ada kendala transportasi yang tidak terduga..."
                            required
                            :error="$errors->first('appealReason')"
                            help="{{ strlen($appealReason) }}/500 karakter (minimal 20)" />
                    </div>

                    <!-- Footer -->
                    <div class="bg-gray-50 dark:bg-gray-800/50 px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                        <x-ui.button 
                            variant="white" 
                            wire:click="$set('showAppealModal', false)">
                            Batal
                        </x-ui.button>
                        <x-ui.button 
                            variant="primary" 
                            wire:click="submitAppeal"
                            wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="submitAppeal">Kirim Banding</span>
                            <span wire:loading wire:target="submitAppeal" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Memproses...
                            </span>
                        </x-ui.button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
