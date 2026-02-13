<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Tukar Jadwal</h1>
            <p class="text-sm text-gray-500">Kelola permintaan tukar jadwal shift</p>
        </div>
        <button wire:click="openForm" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Ajukan Tukar
        </button>
    </div>

    {{-- Tabs --}}
    <div class="border-b border-gray-200">
        <nav class="flex gap-8">
            <button wire:click="setTab('my-requests')" @class(['pb-3 text-sm font-medium border-b-2 -mb-px', 'border-blue-500 text-blue-600' => $activeTab === 'my-requests', 'border-transparent text-gray-500 hover:text-gray-700' => $activeTab !== 'my-requests'])>
                Permintaan Saya
            </button>
            <button wire:click="setTab('received')" @class(['pb-3 text-sm font-medium border-b-2 -mb-px', 'border-blue-500 text-blue-600' => $activeTab === 'received', 'border-transparent text-gray-500 hover:text-gray-700' => $activeTab !== 'received'])>
                Permintaan Masuk
                @if($stats['pending'] > 0 && $activeTab !== 'received')
                <span class="ml-2 px-2 py-0.5 text-xs bg-red-100 text-red-800 rounded-full">{{ $stats['pending'] }}</span>
                @endif
            </button>
            @if($isAdmin)
            <button wire:click="setTab('admin')" @class(['pb-3 text-sm font-medium border-b-2 -mb-px', 'border-blue-500 text-blue-600' => $activeTab === 'admin', 'border-transparent text-gray-500 hover:text-gray-700' => $activeTab !== 'admin'])>
                Persetujuan Admin
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
            <option value="target_approved">Disetujui Target</option>
            <option value="admin_approved">Disetujui Admin</option>
            <option value="target_rejected">Ditolak Target</option>
            <option value="admin_rejected">Ditolak Admin</option>
            <option value="cancelled">Dibatalkan</option>
        </select>
    </div>

    {{-- List --}}
    <div class="bg-white rounded-lg border divide-y">
        @forelse($requests as $req)
        <div wire:key="swap-{{ $req->id }}" wire:click="viewRequest({{ $req->id }})" class="p-4 hover:bg-gray-50 cursor-pointer">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1 min-w-0">
                    {{-- Status --}}
                    <div class="flex items-center gap-2 mb-2">
                        <span @class([
                            'px-2 py-0.5 text-xs rounded-full',
                            'bg-yellow-100 text-yellow-700' => $req->status === 'pending',
                            'bg-blue-100 text-blue-700' => $req->status === 'target_approved',
                            'bg-green-100 text-green-700' => $req->status === 'admin_approved',
                            'bg-red-100 text-red-700' => in_array($req->status, ['target_rejected', 'admin_rejected']),
                            'bg-gray-100 text-gray-700' => $req->status === 'cancelled',
                        ])>
                            {{ match($req->status) {
                                'pending' => 'Menunggu',
                                'target_approved' => 'Disetujui Target',
                                'admin_approved' => 'Selesai',
                                'target_rejected' => 'Ditolak Target',
                                'admin_rejected' => 'Ditolak Admin',
                                'cancelled' => 'Dibatalkan',
                                default => $req->status
                            } }}
                        </span>
                        <span class="text-xs text-gray-400">{{ $req->created_at->diffForHumans() }}</span>
                    </div>

                    {{-- Swap Info --}}
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-xs text-gray-500">Dari</p>
                            <p class="font-medium text-gray-900">{{ $req->requester->name }}</p>
                            @if($req->requesterAssignment)
                            <p class="text-gray-600">{{ \Carbon\Carbon::parse($req->requesterAssignment->date)->format('d M') }} • Sesi {{ $req->requesterAssignment->session }}</p>
                            @endif
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Ke</p>
                            <p class="font-medium text-gray-900">{{ $req->target->name }}</p>
                            @if($req->targetAssignment)
                            <p class="text-gray-600">{{ \Carbon\Carbon::parse($req->targetAssignment->date)->format('d M') }} • Sesi {{ $req->targetAssignment->session }}</p>
                            @endif
                        </div>
                    </div>

                    @if($req->reason)
                    <p class="text-sm text-gray-500 mt-2 truncate">{{ $req->reason }}</p>
                    @endif
                </div>

                {{-- Quick Actions --}}
                <div class="flex items-center gap-2" wire:click.stop>
                    @if($activeTab === 'received' && $req->status === 'pending')
                        <button wire:click="targetApprove({{ $req->id }})" class="p-2 text-green-600 hover:bg-green-50 rounded-lg" title="Setujui">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </button>
                        <button wire:click="targetReject({{ $req->id }})" class="p-2 text-red-600 hover:bg-red-50 rounded-lg" title="Tolak">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    @elseif($activeTab === 'admin' && $req->status === 'target_approved')
                        <button wire:click="openAdminReview({{ $req->id }}, 'approved')" class="p-2 text-green-600 hover:bg-green-50 rounded-lg" title="Setujui">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </button>
                        <button wire:click="openAdminReview({{ $req->id }}, 'rejected')" class="p-2 text-red-600 hover:bg-red-50 rounded-lg" title="Tolak">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    @elseif($activeTab === 'my-requests' && $req->status === 'pending')
                        <button wire:click="cancelRequest({{ $req->id }})" wire:confirm="Batalkan permintaan?" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg" title="Batalkan">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    @endif
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </div>
            </div>
        </div>
        @empty
        <div class="p-12 text-center text-gray-500">
            <p>Belum ada permintaan tukar jadwal</p>
        </div>
        @endforelse
    </div>

    {{ $requests->links() }}

    {{-- Create Form Modal --}}
    @if($showForm)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-black/50" wire:click="closeForm"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Ajukan Tukar Jadwal</h3>
                    
                    <form wire:submit="submitForm" class="space-y-4">
                        {{-- My Assignment --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Jadwal Saya yang Ditukar</label>
                            <select wire:model="selectedAssignment" class="w-full border-gray-300 rounded-lg text-sm">
                                <option value="">Pilih jadwal...</option>
                                @foreach($myAssignments as $a)
                                <option value="{{ $a->id }}">{{ $a->date->format('d M Y') }} - Sesi {{ $a->session }} ({{ $a->time_start }} - {{ $a->time_end }})</option>
                                @endforeach
                            </select>
                            @error('selectedAssignment') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Target Date --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Target</label>
                            <input type="date" wire:model.live="targetDate" min="{{ now()->format('Y-m-d') }}" class="w-full border-gray-300 rounded-lg text-sm">
                            @error('targetDate') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Target Session --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sesi Target</label>
                            <select wire:model.live="targetSession" class="w-full border-gray-300 rounded-lg text-sm">
                                <option value="0">Pilih sesi...</option>
                                <option value="1">Sesi 1 (Pagi)</option>
                                <option value="2">Sesi 2 (Siang)</option>
                                <option value="3">Sesi 3 (Sore)</option>
                            </select>
                            @error('targetSession') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Target User --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tukar dengan</label>
                            @if($availableTargets->count() > 0)
                            <select wire:model="selectedTarget" class="w-full border-gray-300 rounded-lg text-sm">
                                <option value="">Pilih anggota...</option>
                                @foreach($availableTargets as $t)
                                <option value="{{ $t['id'] }}">{{ $t['name'] }} ({{ $t['nim'] }})</option>
                                @endforeach
                            </select>
                            @else
                            <p class="text-sm text-gray-500 p-2 bg-gray-50 rounded-lg">
                                @if($targetDate && $targetSession)
                                Tidak ada anggota yang tersedia di waktu tersebut
                                @else
                                Pilih tanggal dan sesi terlebih dahulu
                                @endif
                            </p>
                            @endif
                            @error('selectedTarget') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Reason --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Alasan</label>
                            <textarea wire:model="reason" rows="3" class="w-full border-gray-300 rounded-lg text-sm" placeholder="Jelaskan alasan tukar jadwal..."></textarea>
                            @error('reason') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
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
                        <h3 class="text-lg font-semibold">Detail Tukar Jadwal</h3>
                        <button wire:click="closeView" class="p-1 text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="space-y-4">
                        {{-- Status --}}
                        <span @class([
                            'inline-block px-3 py-1 text-sm rounded-full',
                            'bg-yellow-100 text-yellow-700' => $viewingRequest->status === 'pending',
                            'bg-blue-100 text-blue-700' => $viewingRequest->status === 'target_approved',
                            'bg-green-100 text-green-700' => $viewingRequest->status === 'admin_approved',
                            'bg-red-100 text-red-700' => in_array($viewingRequest->status, ['target_rejected', 'admin_rejected']),
                            'bg-gray-100 text-gray-700' => $viewingRequest->status === 'cancelled',
                        ])>
                            {{ match($viewingRequest->status) {
                                'pending' => 'Menunggu Persetujuan Target',
                                'target_approved' => 'Menunggu Persetujuan Admin',
                                'admin_approved' => 'Selesai - Jadwal Ditukar',
                                'target_rejected' => 'Ditolak oleh Target',
                                'admin_rejected' => 'Ditolak oleh Admin',
                                'cancelled' => 'Dibatalkan',
                                default => $viewingRequest->status
                            } }}
                        </span>

                        {{-- Requester --}}
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <p class="text-xs text-gray-500 mb-1">Pemohon</p>
                            <p class="font-medium">{{ $viewingRequest->requester->name }}</p>
                            @if($viewingRequest->requesterAssignment)
                            <p class="text-sm text-gray-600">{{ \Carbon\Carbon::parse($viewingRequest->requesterAssignment->date)->format('d M Y') }} • Sesi {{ $viewingRequest->requesterAssignment->session }}</p>
                            <p class="text-sm text-gray-500">{{ $viewingRequest->requesterAssignment->time_start }} - {{ $viewingRequest->requesterAssignment->time_end }}</p>
                            @endif
                        </div>

                        {{-- Target --}}
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <p class="text-xs text-gray-500 mb-1">Target Tukar</p>
                            <p class="font-medium">{{ $viewingRequest->target->name }}</p>
                            @if($viewingRequest->targetAssignment)
                            <p class="text-sm text-gray-600">{{ \Carbon\Carbon::parse($viewingRequest->targetAssignment->date)->format('d M Y') }} • Sesi {{ $viewingRequest->targetAssignment->session }}</p>
                            <p class="text-sm text-gray-500">{{ $viewingRequest->targetAssignment->time_start }} - {{ $viewingRequest->targetAssignment->time_end }}</p>
                            @endif
                        </div>

                        {{-- Reason --}}
                        <div>
                            <p class="text-xs text-gray-500">Alasan</p>
                            <p class="text-gray-700">{{ $viewingRequest->reason }}</p>
                        </div>

                        {{-- Admin Response --}}
                        @if($viewingRequest->admin_responded_at)
                        <div class="p-3 bg-gray-50 rounded-lg text-sm">
                            <p class="text-gray-500">Ditinjau oleh {{ $viewingRequest->adminResponder?->name }} • {{ $viewingRequest->admin_responded_at->format('d M Y H:i') }}</p>
                            @if($viewingRequest->admin_response)
                            <p class="text-gray-700 mt-1">"{{ $viewingRequest->admin_response }}"</p>
                            @endif
                        </div>
                        @endif

                        <p class="text-xs text-gray-400">Diajukan {{ $viewingRequest->created_at->format('d M Y, H:i') }}</p>
                    </div>

                    {{-- Actions --}}
                    <div class="mt-6 flex gap-3">
                        @if($activeTab === 'received' && $viewingRequest->status === 'pending')
                            <button wire:click="targetReject({{ $viewingRequest->id }})" class="flex-1 px-4 py-2 border border-red-300 text-red-600 rounded-lg text-sm font-medium hover:bg-red-50">Tolak</button>
                            <button wire:click="targetApprove({{ $viewingRequest->id }})" class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700">Setujui</button>
                        @elseif($activeTab === 'admin' && $viewingRequest->status === 'target_approved')
                            <button wire:click="openAdminReview({{ $viewingRequest->id }}, 'rejected')" class="flex-1 px-4 py-2 border border-red-300 text-red-600 rounded-lg text-sm font-medium hover:bg-red-50">Tolak</button>
                            <button wire:click="openAdminReview({{ $viewingRequest->id }}, 'approved')" class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700">Setujui & Proses</button>
                        @elseif($activeTab === 'my-requests' && $viewingRequest->status === 'pending')
                            <button wire:click="closeView" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">Tutup</button>
                            <button wire:click="cancelRequest({{ $viewingRequest->id }})" wire:confirm="Batalkan permintaan?" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700">Batalkan</button>
                        @else
                            <button wire:click="closeView" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">Tutup</button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Admin Review Modal --}}
    @if($reviewingId)
    <div class="fixed inset-0 z-[60] overflow-y-auto">
        <div class="fixed inset-0 bg-black/50" wire:click="closeAdminReview"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-sm">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">
                        {{ $reviewAction === 'approved' ? 'Setujui Tukar Jadwal' : 'Tolak Tukar Jadwal' }}
                    </h3>

                    <p class="text-sm text-gray-600 mb-4">
                        {{ $reviewAction === 'approved' 
                            ? 'Jadwal kedua anggota akan langsung ditukar setelah disetujui.' 
                            : 'Permintaan tukar jadwal akan ditolak.' }}
                    </p>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan (opsional)</label>
                        <textarea wire:model="reviewNotes" rows="3" class="w-full border-gray-300 rounded-lg text-sm" placeholder="Tambahkan catatan..."></textarea>
                    </div>

                    <div class="flex gap-3">
                        <button wire:click="closeAdminReview" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">Batal</button>
                        <button wire:click="submitAdminReview" @class(['flex-1 px-4 py-2 rounded-lg text-sm font-medium text-white', 'bg-green-600 hover:bg-green-700' => $reviewAction === 'approved', 'bg-red-600 hover:bg-red-700' => $reviewAction === 'rejected'])>
                            {{ $reviewAction === 'approved' ? 'Ya, Setujui' : 'Ya, Tolak' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
