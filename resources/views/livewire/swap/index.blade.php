<div class="space-y-6">
    <!-- Page Header -->
    <x-layout.page-header title="Tukar Shift">
        <x-slot:actions>
            <x-ui.button 
                variant="primary" 
                :href="route('admin.swap.create')" 
                icon="plus">
                Buat Permintaan
            </x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <!-- Tabs -->
    <x-ui.card :padding="false">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                <button
                    type="button"
                    wire:click="$set('tab', 'my-requests')"
                    class="group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200 
                        {{ $tab === 'my-requests' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                >
                    Permintaan Saya
                </button>
                <button
                    type="button"
                    wire:click="$set('tab', 'received')"
                    class="group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200 
                        {{ $tab === 'received' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                >
                    Permintaan Masuk
                </button>
            </nav>
        </div>

        <!-- Search and Filter -->
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center space-x-4">
                <div class="flex-1">
                    <x-ui.input 
                        wire:model.live.debounce.300ms="search"
                        type="text"
                        placeholder="Cari berdasarkan nama atau NIM..."
                        icon="magnifying-glass"
                    />
                </div>
            </div>
        </div>

        <!-- Swap List -->
        <div class="p-6">
            <div class="space-y-4">
                @forelse($swaps as $swap)
                    <x-ui.card shadow="sm" class="hover:shadow-md transition-shadow">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-3">
                                    @php
                                        $statusVariant = match($swap->status) {
                                            'admin_approved' => 'success',
                                            'target_approved' => 'info',
                                            'pending' => 'warning',
                                            'target_rejected', 'admin_rejected', 'cancelled' => 'danger',
                                            default => 'secondary'
                                        };
                                        $statusLabel = match($swap->status) {
                                            'admin_approved' => 'Disetujui Admin',
                                            'target_approved' => 'Disetujui Target',
                                            'pending' => 'Menunggu',
                                            'target_rejected' => 'Ditolak Target',
                                            'admin_rejected' => 'Ditolak Admin',
                                            'cancelled' => 'Dibatalkan',
                                            default => ucfirst($swap->status)
                                        };
                                    @endphp
                                    <x-ui.badge :variant="$statusVariant">
                                        {{ $statusLabel }}
                                    </x-ui.badge>
                                    <span class="text-sm text-gray-500">
                                        {{ $swap->created_at->diffForHumans() }}
                                    </span>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                                    <div>
                                        <div class="text-xs font-medium text-gray-500 uppercase mb-1">Dari</div>
                                        <div class="font-semibold text-gray-900">{{ $swap->requester->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $swap->requester->nim }}</div>
                                        @if($swap->requesterAssignment && $swap->requesterAssignment->schedule)
                                            <div class="text-sm text-gray-600 mt-1">
                                                {{ $swap->requesterAssignment->schedule->date->format('d/m/Y') }} - 
                                                Sesi {{ $swap->requesterAssignment->schedule->session }}
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="text-xs font-medium text-gray-500 uppercase mb-1">Ke</div>
                                        <div class="font-semibold text-gray-900">{{ $swap->target->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $swap->target->nim }}</div>
                                        @if($swap->targetAssignment && $swap->targetAssignment->schedule)
                                            <div class="text-sm text-gray-600 mt-1">
                                                {{ $swap->targetAssignment->schedule->date->format('d/m/Y') }} - 
                                                Sesi {{ $swap->targetAssignment->schedule->session }}
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                @if($swap->reason)
                                    <div class="text-sm text-gray-700 bg-gray-50 rounded-lg p-3">
                                        <span class="font-medium">Alasan:</span> {{ $swap->reason }}
                                    </div>
                                @endif
                            </div>

                            @if($tab === 'received' && $swap->status === 'pending')
                                <div class="flex space-x-2 ml-4">
                                    <x-ui.button 
                                        wire:click="acceptRequest({{ $swap->id }})" 
                                        variant="success" 
                                        size="sm">
                                        Terima
                                    </x-ui.button>
                                    <x-ui.button 
                                        wire:click="rejectRequest({{ $swap->id }})" 
                                        variant="danger" 
                                        size="sm">
                                        Tolak
                                    </x-ui.button>
                                </div>
                            @elseif($tab === 'my-requests' && $swap->status === 'pending')
                                <x-ui.button 
                                    wire:click="cancelRequest({{ $swap->id }})" 
                                    wire:confirm="Batalkan permintaan?"
                                    variant="white" 
                                    size="sm" 
                                    class="ml-4">
                                    Batalkan
                                </x-ui.button>
                            @endif
                        </div>
                    </x-ui.card>
                @empty
                    <x-layout.empty-state 
                        icon="arrow-path" 
                        title="Tidak ada permintaan tukar shift"
                        description="Belum ada permintaan tukar shift pada tab ini" />
                @endforelse
            </div>
        </div>
    </x-ui.card>

    <!-- Pagination -->
    <div>
        {{ $swaps->links() }}
    </div>
</div>
