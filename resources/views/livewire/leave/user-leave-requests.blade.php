<div class="space-y-6">
    
    
    

    <x-layout.page-header 
        title="Riwayat Cuti/Izin Saya"
        description="Kelola dan pantau permohonan cuti/izin Anda">
        <x-slot:actions>
            <x-ui.button 
                variant="primary" 
                icon="plus"
                :href="route('admin.leave.create')">
                Ajukan Baru
            </x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <x-layout.grid cols="4">
        <x-ui.card padding="true">
            <p class="text-sm text-gray-600">Total Pengajuan</p>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
        </x-ui.card>
        
        <x-ui.card padding="true">
            <p class="text-sm text-gray-600">Menunggu</p>
            <p class="text-2xl font-bold text-warning-600">{{ $stats['pending'] }}</p>
        </x-ui.card>
        
        <x-ui.card padding="true">
            <p class="text-sm text-gray-600">Disetujui</p>
            <p class="text-2xl font-bold text-success-600">{{ $stats['approved'] }}</p>
        </x-ui.card>
        
        <x-ui.card padding="true">
            <p class="text-sm text-gray-600">Ditolak</p>
            <p class="text-2xl font-bold text-danger-600">{{ $stats['rejected'] }}</p>
        </x-ui.card>
    </x-layout.grid>

    <x-ui.card padding="true">
        <div class="flex items-center space-x-4">
            <label class="text-sm font-medium text-gray-700">Filter Status:</label>
            <x-ui.select 
                name="statusFilter" 
                wire:model.live="statusFilter">
                <option value="all">Semua</option>
                <option value="pending">Menunggu</option>
                <option value="approved">Disetujui</option>
                <option value="rejected">Ditolak</option>
                <option value="cancelled">Dibatalkan</option>
            </x-ui.select>
        </div>
    </x-ui.card>

    <div class="space-y-4">
        @forelse($requests as $request)
        <x-ui.card padding="true">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center space-x-3 mb-3">
                        <x-ui.badge 
                            :variant="match($request->status) {
                                'pending' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'cancelled' => 'gray',
                                default => 'gray'
                            }"
                            size="sm">
                            {{ match($request->status) {
                                'pending' => 'Menunggu',
                                'approved' => 'Disetujui',
                                'rejected' => 'Ditolak',
                                'cancelled' => 'Dibatalkan',
                                default => $request->status
                            } }}
                        </x-ui.badge>
                        <x-ui.badge 
                            :variant="match($request->leave_type) {
                                'permission' => 'info',
                                'sick' => 'danger',
                                'emergency' => 'warning',
                                'other' => 'secondary',
                                default => 'gray'
                            }"
                            size="sm">
                            {{ $request->getLeaveTypeLabel() }}
                        </x-ui.badge>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <p class="text-xs text-gray-500">Periode</p>
                            <p class="font-semibold text-gray-900">
                                {{ $request->start_date->format('d M Y') }} - {{ $request->end_date->format('d M Y') }}
                            </p>
                            <p class="text-sm text-gray-600">{{ $request->total_days }} hari</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Diajukan</p>
                            <p class="text-sm text-gray-900">{{ $request->created_at->format('d M Y H:i') }}</p>
                        </div>
                        @if($request->reviewed_at)
                        <div>
                            <p class="text-xs text-gray-500">Ditinjau oleh</p>
                            <p class="text-sm text-gray-900">{{ $request->reviewer->name ?? '-' }}</p>
                            <p class="text-xs text-gray-600">{{ $request->reviewed_at->format('d M Y H:i') }}</p>
                        </div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <p class="text-xs text-gray-500 mb-1">Alasan:</p>
                        <p class="text-sm text-gray-700">{{ $request->reason }}</p>
                    </div>

                    @if($request->attachment)
                    <div class="flex items-center space-x-2 text-sm text-info-600">
                        <x-ui.icon name="paper-clip" class="w-4 h-4" />
                        <a href="{{ $this->getAttachmentUrl($request->attachment) }}" target="_blank" class="hover:underline">
                            Lihat Lampiran
                        </a>
                    </div>
                    @endif

                    @if($request->review_notes)
                    <div class="mt-3 p-3 bg-gray-50 rounded-lg">
                        <p class="text-xs text-gray-500 mb-1">Catatan Review:</p>
                        <p class="text-sm text-gray-700">{{ $request->review_notes }}</p>
                    </div>
                    @endif
                </div>

                @if($request->canCancel())
                <div class="ml-4">
                    <x-ui.button 
                        variant="ghost" 
                        size="sm"
                        wire:click="cancel({{ $request->id }})" 
                        wire:confirm="Yakin ingin membatalkan permohonan ini?">
                        Batalkan
                    </x-ui.button>
                </div>
                @endif
            </div>
        </x-ui.card>
        @empty
        <x-ui.card padding="true">
            <x-layout.empty-state 
                icon="document-text"
                title="Belum ada pengajuan cuti/izin"
                description="Klik tombol 'Ajukan Baru' untuk membuat pengajuan" />
        </x-ui.card>
        @endforelse
    </div>

    @if($requests->hasPages())
        <x-data.pagination :paginator="$requests" />
    @endif

    <div wire:loading class="fixed inset-0 bg-gray-900/50 flex items-center justify-center z-40">
        <x-ui.card padding="true">
            <div class="text-center">
                <x-ui.spinner size="lg" class="mx-auto mb-4" />
                <p class="text-gray-700 font-medium">Memuat...</p>
            </div>
        </x-ui.card>
    </div>
</div>
