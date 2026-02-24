<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Laporan Penalti</h1>
            <p class="text-sm text-gray-500 mt-1">
                {{ \Carbon\Carbon::parse($dateFrom)->translatedFormat('d M Y') }}
                @if($dateFrom !== $dateTo) - {{ \Carbon\Carbon::parse($dateTo)->translatedFormat('d M Y') }} @endif
            </p>
        </div>
    </div>

    {{-- Date Presets --}}
    <div class="flex flex-wrap gap-2">
        @foreach(['today' => 'Hari Ini', 'yesterday' => 'Kemarin', 'week' => 'Minggu Ini', 'month' => 'Bulan Ini'] as $key => $label)
            <button wire:click="setPeriod('{{ $key }}')" 
                class="px-3 py-1.5 text-sm font-medium rounded-lg transition-colors {{ $period === $key ? 'bg-primary-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Penalti</p>
                    <p class="text-xl sm:text-2xl font-bold text-red-600 dark:text-red-400 mt-1">{{ number_format($this->stats->total ?? 0) }}</p>
                </div>
                <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                    <x-ui.icon name="exclamation-triangle" class="w-5 h-5 text-red-600 dark:text-red-400" />
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Aktif</p>
                    <p class="text-xl sm:text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">{{ number_format($this->stats->active ?? 0) }}</p>
                </div>
                <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center">
                    <x-ui.icon name="clock" class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Banding</p>
                    <p class="text-xl sm:text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ number_format($this->stats->appealed ?? 0) }}</p>
                </div>
                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                    <x-ui.icon name="scale" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Poin</p>
                    <p class="text-xl sm:text-2xl font-bold text-violet-600 dark:text-violet-400 mt-1">{{ number_format($this->stats->total_points ?? 0) }}</p>
                </div>
                <div class="w-10 h-10 bg-violet-100 dark:bg-violet-900/30 rounded-lg flex items-center justify-center">
                    <x-ui.icon name="chart-bar" class="w-5 h-5 text-violet-600 dark:text-violet-400" />
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-md p-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <x-ui.input type="date" name="dateFrom" label="Dari Tanggal" wire:model.live.debounce.500ms="dateFrom" />
            <x-ui.input type="date" name="dateTo" label="Sampai Tanggal" wire:model.live.debounce.500ms="dateTo" />
            
            <x-ui.dropdown-select 
                label="User"
                wire="userFilter"
                :options="array_merge(
                    [['value' => 'all', 'label' => 'Semua User']],
                    collect($this->users)->map(fn($u) => ['value' => (string)$u->id, 'label' => $u->name])->toArray()
                )"
                placeholder="Semua User"
                :searchable="true"
            />
            
            <x-ui.dropdown-select 
                label="Status"
                wire="statusFilter"
                :options="[
                    ['value' => 'all', 'label' => 'Semua Status'],
                    ['value' => 'active', 'label' => 'Aktif'],
                    ['value' => 'appealed', 'label' => 'Banding'],
                    ['value' => 'dismissed', 'label' => 'Dibatalkan'],
                    ['value' => 'expired', 'label' => 'Kadaluarsa'],
                ]"
                placeholder="Semua Status"
            />
        </div>
    </div>

    {{-- Data Table --}}
    <x-ui.card padding="false">
        {{-- Mobile Cards --}}
        <div class="sm:hidden divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($penalties as $penalty)
                @php
                    $statusConfig = match($penalty->status) {
                        'active' => ['label' => 'Aktif', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-400'],
                        'appealed' => ['label' => 'Banding', 'class' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-400'],
                        'dismissed' => ['label' => 'Dibatalkan', 'class' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400'],
                        'expired' => ['label' => 'Kadaluarsa', 'class' => 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-500'],
                        default => ['label' => $penalty->status, 'class' => 'bg-gray-100 text-gray-700']
                    };
                @endphp
                <div class="p-3 space-y-1">
                    <div class="flex justify-between items-start">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-sm text-gray-900 dark:text-white truncate">{{ $penalty->user->name ?? '-' }}</p>
                            <p class="text-xs text-gray-500">{{ $penalty->user->nim ?? '-' }}</p>
                        </div>
                        <span class="px-1.5 py-0.5 rounded text-xs font-medium {{ $statusConfig['class'] }} shrink-0 ml-2">
                            {{ $statusConfig['label'] }}
                        </span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-gray-500">{{ $penalty->date->format('d/m/Y') }}</span>
                        <span class="font-semibold text-red-600 dark:text-red-400">{{ $penalty->points }} poin</span>
                    </div>
                    <div class="text-xs">
                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ $penalty->penaltyType->name ?? '-' }}</span>
                        <span class="text-gray-400">({{ $penalty->penaltyType->code ?? '-' }})</span>
                    </div>
                    @if($penalty->description)
                        <p class="text-xs text-gray-500 truncate">{{ $penalty->description }}</p>
                    @endif
                    @if($penalty->status === 'appealed' && auth()->user()->can('kelola_penalti'))
                        <div class="mt-2 flex justify-end">
                            <x-ui.button 
                                variant="ghost" 
                                size="sm"
                                wire:click="openReviewModal({{ $penalty->id }})">
                                Review Banding
                            </x-ui.button>
                        </div>
                    @endif
                </div>
            @empty
                <div class="p-8 text-center text-gray-400 text-sm">Tidak ada data penalti</div>
            @endforelse
        </div>

        {{-- Desktop Table --}}
        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/50 text-xs text-gray-500 uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">Tanggal</th>
                        <th class="px-4 py-3 text-left">Nama</th>
                        <th class="px-4 py-3 text-left">Jenis</th>
                        <th class="px-4 py-3 text-center">Poin</th>
                        <th class="px-4 py-3 text-left">Deskripsi</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($penalties as $penalty)
                        @php
                            $statusConfig = match($penalty->status) {
                                'active' => ['label' => 'Aktif', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-400'],
                                'appealed' => ['label' => 'Banding', 'class' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-400'],
                                'dismissed' => ['label' => 'Dibatalkan', 'class' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400'],
                                'expired' => ['label' => 'Kadaluarsa', 'class' => 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-500'],
                                default => ['label' => $penalty->status, 'class' => 'bg-gray-100 text-gray-700']
                            };
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30">
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $penalty->date->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-900 dark:text-white">{{ $penalty->user->name ?? '-' }}</p>
                                <p class="text-xs text-gray-500">{{ $penalty->user->nim ?? '-' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-gray-900 dark:text-white">{{ $penalty->penaltyType->name ?? '-' }}</p>
                                <p class="text-xs text-gray-500">{{ $penalty->penaltyType->code ?? '-' }}</p>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-semibold text-red-600 dark:text-red-400">{{ $penalty->points }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400 max-w-xs truncate">
                                <div class="flex flex-col">
                                    <span>{{ $penalty->description ?? '-' }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-0.5 rounded text-xs font-medium {{ $statusConfig['class'] }}">
                                    {{ $statusConfig['label'] }}
                                </span>
                                @if($penalty->status === 'appealed' && $penalty->appeal_reason)
                                    <div class="text-xs text-gray-500 mt-1 text-left max-w-xs mx-auto truncate" title="{{ $penalty->appeal_reason }}">
                                        Ref: {{ Str::limit($penalty->appeal_reason, 20) }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($penalty->status === 'appealed' && auth()->user()->can('kelola_penalti'))
                                    <x-ui.button 
                                        variant="ghost" 
                                        size="sm"
                                        wire:click="openReviewModal({{ $penalty->id }})">
                                        Review Banding
                                    </x-ui.button>
                                @else
                                    <span class="text-sm text-gray-500">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-gray-400 text-sm">Tidak ada data penalti</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($penalties->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $penalties->links() }}
            </div>
        @endif
    </x-ui.card>

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

    <!-- Review Appeal Modal -->
    @if($showReviewModal && $selectedPenalty)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data x-on:keydown.escape.window="$wire.set('showReviewModal', false)">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" wire:click="$set('showReviewModal', false)"></div>
            
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-xl bg-white dark:bg-gray-800 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-2xl">
                    <!-- Header -->
                    <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex items-center justify-between bg-gray-50 dark:bg-gray-800/50">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Review Banding Penalti</h3>
                        <button wire:click="$set('showReviewModal', false)" class="text-gray-400 hover:text-gray-500 transition-colors">
                            <x-ui.icon name="x" class="w-6 h-6" />
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Anggota</label>
                                    <div class="mt-1 font-bold text-gray-900 dark:text-white">{{ $selectedPenalty->user->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $selectedPenalty->user->nim }}</div>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Jenis Penalti</label>
                                    <div class="mt-1 font-bold text-gray-900 dark:text-white">{{ $selectedPenalty->penaltyType->name }}</div>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Poin</label>
                                    <div class="mt-1 font-extrabold text-red-600 dark:text-red-400">{{ $selectedPenalty->points }}</div>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal</label>
                                    <div class="mt-1 text-gray-900 dark:text-gray-200">{{ $selectedPenalty->date->format('d/m/Y') }}</div>
                                </div>
                                <div class="col-span-2">
                                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Deskripsi Penalti</label>
                                    <div class="mt-1 text-gray-700 dark:text-gray-300 leading-relaxed italic">"{{ $selectedPenalty->description }}"</div>
                                </div>
                                <div class="col-span-2">
                                    <label class="text-xs font-semibold text-amber-700 dark:text-amber-400 uppercase tracking-wider">Alasan Banding</label>
                                    <div class="mt-1 text-gray-900 dark:text-gray-200 bg-amber-50 dark:bg-amber-900/20 p-3 rounded border border-amber-100 dark:border-amber-800/30 leading-relaxed">
                                        {{ $selectedPenalty->appeal_reason }}
                                    </div>
                                </div>
                                <div class="col-span-2">
                                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal Banding</label>
                                    <div class="mt-1 text-gray-900 dark:text-gray-200">{{ $selectedPenalty->appealed_at->format('d/m/Y H:i') }}</div>
                                </div>
                            </div>
                        </div>

                        <x-ui.alert variant="info" :icon="true">
                            <strong>Setujui Banding:</strong> Penalti akan dibatalkan (Dismissed).<br>
                            <strong>Tolak Banding:</strong> Penalti tetap aktif (Active).
                        </x-ui.alert>

                        <x-ui.textarea 
                            name="reviewNotes"
                            label="Catatan Review Admin"
                            wire:model="reviewNotes"
                            rows="3"
                            placeholder="Berikan alasan keputusan Anda (minimal 10 karakter)..."
                            required
                            :error="$errors->first('reviewNotes')"
                            help="{{ strlen($reviewNotes) }}/500 karakter" />
                    </div>

                    <!-- Footer -->
                    <div class="bg-gray-50 dark:bg-gray-800/50 px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row justify-end gap-3">
                        <x-ui.button 
                            variant="white" 
                            wire:click="$set('showReviewModal', false)">
                            Batal
                        </x-ui.button>
                        <div class="flex gap-3">
                            <x-ui.button 
                                variant="danger" 
                                wire:click="rejectAppeal"
                                wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="rejectAppeal">Tolak Banding</span>
                                <span wire:loading wire:target="rejectAppeal" class="flex items-center">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    Memproses...
                                </span>
                            </x-ui.button>
                            <x-ui.button 
                                variant="success" 
                                wire:click="approveAppeal"
                                wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="approveAppeal">Setujui Banding</span>
                                <span wire:loading wire:target="approveAppeal" class="flex items-center">
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
        </div>
    @endif
</div>
