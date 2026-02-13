<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Izin & Cuti</h1>
            <p class="text-sm text-gray-500">Kelola pengajuan izin dan cuti</p>
        </div>
        <button wire:click="openForm" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Ajukan Izin
        </button>
    </div>

    {{-- Tabs --}}
    <div class="border-b border-gray-200">
        <nav class="flex gap-8">
            <button wire:click="setTab('my-requests')" @class(['pb-3 text-sm font-medium border-b-2 -mb-px', 'border-blue-500 text-blue-600' => $activeTab === 'my-requests', 'border-transparent text-gray-500 hover:text-gray-700' => $activeTab !== 'my-requests'])>
                Pengajuan Saya
                @if($stats['pending'] > 0 && $activeTab === 'my-requests')
                <span class="ml-2 px-2 py-0.5 text-xs bg-yellow-100 text-yellow-800 rounded-full">{{ $stats['pending'] }}</span>
                @endif
            </button>
            @if($isAdmin)
            <button wire:click="setTab('approvals')" @class(['pb-3 text-sm font-medium border-b-2 -mb-px', 'border-blue-500 text-blue-600' => $activeTab === 'approvals', 'border-transparent text-gray-500 hover:text-gray-700' => $activeTab !== 'approvals'])>
                Persetujuan
                @if($stats['pending'] > 0 && $activeTab === 'approvals')
                <span class="ml-2 px-2 py-0.5 text-xs bg-red-100 text-red-800 rounded-full">{{ $stats['pending'] }}</span>
                @endif
            </button>
            @endif
        </nav>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-lg border p-4">
            <p class="text-2xl font-bold text-yellow-600">{{ $stats['pending'] }}</p>
            <p class="text-sm text-gray-500">Menunggu</p>
        </div>
        <div class="bg-white rounded-lg border p-4">
            <p class="text-2xl font-bold text-green-600">{{ $stats['approved'] }}</p>
            <p class="text-sm text-gray-500">Disetujui</p>
        </div>
        <div class="bg-white rounded-lg border p-4">
            <p class="text-2xl font-bold text-red-600">{{ $stats['rejected'] }}</p>
            <p class="text-sm text-gray-500">Ditolak</p>
        </div>
    </div>

    {{-- Filter --}}
    <div class="flex gap-2">
        <select wire:model.live="statusFilter" class="text-sm border-gray-300 rounded-lg">
            <option value="all">Semua Status</option>
            <option value="pending">Menunggu</option>
            <option value="approved">Disetujui</option>
            <option value="rejected">Ditolak</option>
        </select>
    </div>

    {{-- List --}}
    <div class="bg-white rounded-lg border divide-y">
        @forelse($requests as $req)
        <div wire:key="req-{{ $req->id }}" wire:click="viewRequest({{ $req->id }})" class="p-4 hover:bg-gray-50 cursor-pointer flex items-center justify-between">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                    @if($activeTab === 'approvals' && $req->user)
                    <span class="font-medium text-gray-900">{{ $req->user->name }}</span>
                    <span class="text-gray-300">•</span>
                    @endif
                    <span @class(['px-2 py-0.5 text-xs rounded-full', 'bg-blue-100 text-blue-700' => $req->leave_type === 'permission', 'bg-red-100 text-red-700' => $req->leave_type === 'sick', 'bg-orange-100 text-orange-700' => $req->leave_type === 'emergency', 'bg-blue-100 text-blue-700' => $req->leave_type === 'other'])>
                        {{ $req->getLeaveTypeLabel() }}
                    </span>
                    <span @class(['px-2 py-0.5 text-xs rounded-full', 'bg-yellow-100 text-yellow-700' => $req->status === 'pending', 'bg-green-100 text-green-700' => $req->status === 'approved', 'bg-red-100 text-red-700' => $req->status === 'rejected', 'bg-gray-100 text-gray-700' => $req->status === 'cancelled'])>
                        {{ match($req->status) { 'pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'cancelled' => 'Dibatalkan', default => $req->status } }}
                    </span>
                </div>
                <p class="text-sm text-gray-600">{{ $req->start_date->format('d M') }} - {{ $req->end_date->format('d M Y') }} ({{ $req->total_days }} hari)</p>
                <p class="text-sm text-gray-400 truncate">{{ $req->reason }}</p>
            </div>
            <div class="flex items-center gap-2" wire:click.stop>
                @if($activeTab === 'approvals' && $req->status === 'pending')
                <button wire:click="openReview({{ $req->id }}, 'approved')" class="p-2 text-green-600 hover:bg-green-50 rounded-lg" title="Setujui">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </button>
                <button wire:click="openReview({{ $req->id }}, 'rejected')" class="p-2 text-red-600 hover:bg-red-50 rounded-lg" title="Tolak">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
                @endif
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </div>
        </div>
        @empty
        <div class="p-12 text-center text-gray-500">
            <p>Belum ada pengajuan</p>
        </div>
        @endforelse
    </div>

    {{ $requests->links() }}

    {{-- Create Form Modal --}}
    @if($showForm)
    <div class="fixed inset-0 z-50 overflow-y-auto" x-data x-init="document.body.classList.add('overflow-hidden')" x-on:remove="document.body.classList.remove('overflow-hidden')">
        <div class="fixed inset-0 bg-black/50" wire:click="closeForm"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md" @click.stop>
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Ajukan Izin/Cuti</h3>
                    
                    <form wire:submit="submitForm" class="space-y-4">
                        {{-- Type --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Jenis</label>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach(['permission' => 'Izin', 'sick' => 'Sakit', 'emergency' => 'Darurat', 'other' => 'Lainnya'] as $val => $label)
                                <button type="button" wire:click="$set('leave_type', '{{ $val }}')" @class(['p-3 border rounded-lg text-sm font-medium transition', 'border-blue-500 bg-blue-50 text-blue-700' => $leave_type === $val, 'border-gray-200 text-gray-600 hover:border-gray-300' => $leave_type !== $val])>
                                    {{ $label }}
                                </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Dates --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mulai</label>
                                <input type="date" wire:model="start_date" min="{{ now()->format('Y-m-d') }}" class="w-full border-gray-300 rounded-lg text-sm">
                                @error('start_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Selesai</label>
                                <input type="date" wire:model="end_date" min="{{ $start_date }}" class="w-full border-gray-300 rounded-lg text-sm">
                                @error('end_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        {{-- Reason --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Alasan</label>
                            <textarea wire:model="reason" rows="3" class="w-full border-gray-300 rounded-lg text-sm" placeholder="Jelaskan alasan..."></textarea>
                            @error('reason') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Attachment --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Lampiran (opsional)</label>
                            <input type="file" wire:model="attachment" accept=".jpg,.jpeg,.png,.pdf" class="w-full text-sm">
                            @error('attachment') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Actions --}}
                        <div class="flex gap-3 pt-4">
                            <button type="button" wire:click="closeForm" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">Batal</button>
                            <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="submitForm">Kirim</span>
                                <span wire:loading wire:target="submitForm">Mengirim...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Detail Modal --}}
    @if($viewingRequest)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-black/50" wire:click="closeView"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">Detail Pengajuan</h3>
                        <button wire:click="closeView" class="p-1 text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="space-y-4">
                        {{-- Status badges --}}
                        <div class="flex gap-2">
                            <span @class(['px-3 py-1 text-sm rounded-full', 'bg-yellow-100 text-yellow-700' => $viewingRequest->status === 'pending', 'bg-green-100 text-green-700' => $viewingRequest->status === 'approved', 'bg-red-100 text-red-700' => $viewingRequest->status === 'rejected', 'bg-gray-100 text-gray-700' => $viewingRequest->status === 'cancelled'])>
                                {{ match($viewingRequest->status) { 'pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'cancelled' => 'Dibatalkan', default => $viewingRequest->status } }}
                            </span>
                            <span class="px-3 py-1 text-sm rounded-full bg-gray-100 text-gray-700">{{ $viewingRequest->getLeaveTypeLabel() }}</span>
                        </div>

                        @if($activeTab === 'approvals' && $viewingRequest->user)
                        <div>
                            <p class="text-xs text-gray-500">Pemohon</p>
                            <p class="font-medium">{{ $viewingRequest->user->name }}</p>
                        </div>
                        @endif

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-gray-500">Mulai</p>
                                <p class="font-medium">{{ $viewingRequest->start_date->format('d M Y') }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Selesai</p>
                                <p class="font-medium">{{ $viewingRequest->end_date->format('d M Y') }}</p>
                            </div>
                        </div>

                        <div class="p-3 bg-blue-50 rounded-lg text-sm text-blue-700">
                            Total: <strong>{{ $viewingRequest->total_days }} hari</strong>
                        </div>

                        <div>
                            <p class="text-xs text-gray-500">Alasan</p>
                            <p class="text-gray-700">{{ $viewingRequest->reason }}</p>
                        </div>

                        @if($viewingRequest->attachment)
                        <a href="{{ Storage::url($viewingRequest->attachment) }}" target="_blank" class="inline-flex items-center gap-2 text-sm text-blue-600 hover:underline">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                            Lihat Lampiran
                        </a>
                        @endif

                        @if($viewingRequest->reviewed_at)
                        <div class="p-3 bg-gray-50 rounded-lg text-sm">
                            <p class="text-gray-500">Ditinjau oleh {{ $viewingRequest->reviewer?->name }} • {{ $viewingRequest->reviewed_at->format('d M Y H:i') }}</p>
                            @if($viewingRequest->review_notes)
                            <p class="text-gray-700 mt-1">"{{ $viewingRequest->review_notes }}"</p>
                            @endif
                        </div>
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="mt-6 flex gap-3">
                        @if($activeTab === 'approvals' && $viewingRequest->status === 'pending')
                            <button wire:click="openReview({{ $viewingRequest->id }}, 'rejected')" class="flex-1 px-4 py-2 border border-red-300 text-red-600 rounded-lg text-sm font-medium hover:bg-red-50">Tolak</button>
                            <button wire:click="openReview({{ $viewingRequest->id }}, 'approved')" class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700">Setujui</button>
                        @elseif($activeTab === 'my-requests' && $viewingRequest->status === 'pending')
                            <button wire:click="closeView" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">Tutup</button>
                            <button wire:click="cancelRequest({{ $viewingRequest->id }})" wire:confirm="Yakin ingin membatalkan?" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700">Batalkan</button>
                        @else
                            <button wire:click="closeView" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">Tutup</button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Review Modal --}}
    @if($reviewingId)
    <div class="fixed inset-0 z-[60] overflow-y-auto">
        <div class="fixed inset-0 bg-black/50" wire:click="closeReview"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-sm">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">
                        {{ $reviewAction === 'approved' ? 'Setujui Pengajuan' : 'Tolak Pengajuan' }}
                    </h3>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan (opsional)</label>
                        <textarea wire:model="reviewNotes" rows="3" class="w-full border-gray-300 rounded-lg text-sm" placeholder="Tambahkan catatan..."></textarea>
                    </div>

                    <div class="flex gap-3">
                        <button wire:click="closeReview" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">Batal</button>
                        <button wire:click="submitReview" @class(['flex-1 px-4 py-2 rounded-lg text-sm font-medium text-white', 'bg-green-600 hover:bg-green-700' => $reviewAction === 'approved', 'bg-red-600 hover:bg-red-700' => $reviewAction === 'rejected'])>
                            {{ $reviewAction === 'approved' ? 'Ya, Setujui' : 'Ya, Tolak' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
