<div class="space-y-6">
    <x-layout.page-header 
        title="Permintaan Cuti"
        description="Kelola permintaan cuti anggota">
        <x-slot:actions>
            <x-ui.button 
                variant="primary" 
                icon="plus"
                :href="route('admin.leave.create')">
                Ajukan Cuti
            </x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <x-layout.grid cols="4">
        <x-ui.card padding="true">
            <div class="text-2xl font-bold text-gray-900">{{ $summary['total'] }}</div>
            <div class="text-sm text-gray-600">Total Pengajuan</div>
        </x-ui.card>
        
        <x-ui.card padding="true">
            <div class="text-2xl font-bold text-success-600">{{ $summary['approved'] }}</div>
            <div class="text-sm text-gray-600">Disetujui</div>
        </x-ui.card>
        
        <x-ui.card padding="true">
            <div class="text-2xl font-bold text-warning-600">{{ $summary['pending'] }}</div>
            <div class="text-sm text-gray-600">Menunggu</div>
        </x-ui.card>
        
        <x-ui.card padding="true">
            <div class="text-2xl font-bold text-danger-600">{{ $summary['rejected'] }}</div>
            <div class="text-sm text-gray-600">Ditolak</div>
        </x-ui.card>
    </x-layout.grid>

    <x-ui.card padding="true">
        <x-ui.select 
            name="statusFilter" 
            wire:model.live="statusFilter"
            label="Filter Status">
            <option value="">Semua Status</option>
            <option value="pending">Menunggu</option>
            <option value="approved">Disetujui</option>
            <option value="rejected">Ditolak</option>
            <option value="cancelled">Dibatalkan</option>
        </x-ui.select>
    </x-ui.card>

    <x-ui.card padding="false">
        <x-data.table :headers="['Tanggal', 'Jenis Cuti', 'Durasi', 'Alasan', 'Status', 'Aksi']">
            @forelse($leaves as $leave)
                <x-data.table-row>
                    <x-data.table-cell>
                        <div class="text-sm">
                            <div class="font-medium">{{ $leave->start_date?->format('d/m/Y') }}</div>
                            <div class="text-gray-500">s/d {{ $leave->end_date?->format('d/m/Y') }}</div>
                        </div>
                    </x-data.table-cell>
                    <x-data.table-cell>{{ $leave->getLeaveTypeLabel() }}</x-data.table-cell>
                    <x-data.table-cell>{{ $leave->total_days }} hari</x-data.table-cell>
                    <x-data.table-cell>
                        <div class="max-w-xs truncate">{{ $leave->reason }}</div>
                    </x-data.table-cell>
                    <x-data.table-cell>
                        <x-ui.badge 
                            :variant="match($leave->status) {
                                'approved' => 'success',
                                'pending' => 'warning',
                                'rejected' => 'danger',
                                default => 'gray'
                            }">
                            {{ ucfirst($leave->status) }}
                        </x-ui.badge>
                    </x-data.table-cell>
                    <x-data.table-cell>
                        @if($leave->status === 'pending')
                            <x-ui.button 
                                variant="ghost" 
                                size="sm"
                                wire:click="cancelRequest({{ $leave->id }})" 
                                wire:confirm="Batalkan permintaan cuti?">
                                Batalkan
                            </x-ui.button>
                        @endif
                    </x-data.table-cell>
                </x-data.table-row>
            @empty
                <x-data.table-row>
                    <x-data.table-cell colspan="6">
                        <x-layout.empty-state 
                            icon="document-text"
                            title="Belum ada permintaan cuti"
                            description="Belum ada data permintaan cuti yang tersedia" />
                    </x-data.table-cell>
                </x-data.table-row>
            @endforelse
        </x-data.table>
    </x-ui.card>

    <x-data.pagination :paginator="$leaves" />
</div>
