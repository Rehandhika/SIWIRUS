<div class="p-6">
    
    
    

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Persetujuan Cuti/Izin</h1>
        <p class="mt-1 text-sm text-gray-600">Tinjau dan setujui pengajuan cuti/izin</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-600">Menunggu</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $stats['pending'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-600">Disetujui</p>
            <p class="text-2xl font-bold text-green-600">{{ $stats['approved'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-600">Ditolak</p>
            <p class="text-2xl font-bold text-red-600">{{ $stats['rejected'] }}</p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <select wire:model.live="statusFilter" class="px-4 py-2 border border-gray-300 rounded-lg">
            <option value="pending">Menunggu</option>
            <option value="approved">Disetujui</option>
            <option value="rejected">Ditolak</option>
            <option value="all">Semua</option>
        </select>
    </div>

    <div class="space-y-4">
        @forelse($requests as $request)
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-start justify-between mb-4">
                <div class="flex-1">
                    <div class="flex items-center space-x-3 mb-3">
                        <span @class([
                            'px-3 py-1 text-xs font-semibold rounded-full',
                            'bg-yellow-100 text-yellow-800' => $request->status === 'pending',
                            'bg-green-100 text-green-800' => $request->status === 'approved',
                            'bg-red-100 text-red-800' => $request->status === 'rejected',
                        ])>
                            {{ match($request->status) {
                                'pending' => 'Menunggu',
                                'approved' => 'Disetujui',
                                'rejected' => 'Ditolak',
                                default => $request->status
                            } }}
                        </span>
                        <span @class([
                            'px-3 py-1 text-xs font-medium rounded-full',
                            'bg-blue-100 text-blue-800' => $request->leave_type === 'permission',
                            'bg-red-100 text-red-800' => $request->leave_type === 'sick',
                            'bg-yellow-100 text-yellow-800' => $request->leave_type === 'emergency',
                            'bg-blue-100 text-blue-800' => $request->leave_type === 'other',
                        ])>
                            {{ $request->getLeaveTypeLabel() }}
                        </span>
                    </div>

                    <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $request->user->name }}</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
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
                    </div>

                    <div class="mb-3">
                        <p class="text-xs text-gray-500 mb-1">Alasan:</p>
                        <p class="text-sm text-gray-700">{{ $request->reason }}</p>
                    </div>

                    @if($request->attachment)
                    <a href="{{ Storage::url($request->attachment) }}" target="_blank" class="inline-flex items-center space-x-2 text-sm text-blue-600 hover:underline">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                        </svg>
                        <span>Lihat Lampiran</span>
                    </a>
                    @endif

                    @if($request->reviewed_at)
                    <div class="mt-3 p-3 bg-gray-50 rounded-lg">
                        <p class="text-xs text-gray-500">Ditinjau oleh {{ $request->reviewer->name }} pada {{ $request->reviewed_at->format('d M Y H:i') }}</p>
                        @if($request->review_notes)
                        <p class="text-sm text-gray-700 mt-1">{{ $request->review_notes }}</p>
                        @endif
                    </div>
                    @endif
                </div>

                @if($request->isPending())
                <div class="ml-4 flex flex-col space-y-2">
                    <button wire:click="openReview({{ $request->id }}, 'approved')" 
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm">
                        Setujui
                    </button>
                    <button wire:click="openReview({{ $request->id }}, 'rejected')" 
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm">
                        Tolak
                    </button>
                </div>
                @endif
            </div>
        </div>
        @empty
        <div class="bg-white rounded-lg shadow p-12 text-center">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-gray-500 font-medium">Tidak ada pengajuan</p>
        </div>
        @endforelse
    </div>

    @if($requests->hasPages())
    <div class="mt-6">
        {{ $requests->links() }}
    </div>
    @endif

    @if($reviewModal)
    <div class="fixed inset-0 bg-gray-900/75 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full mx-4">
            <div class="p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4">
                    {{ $reviewAction === 'approved' ? 'Setujui' : 'Tolak' }} Pengajuan
                </h3>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Catatan (Opsional)</label>
                    <textarea wire:model="review_notes" rows="3" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Tambahkan catatan..."></textarea>
                </div>

                <div class="flex justify-end space-x-3">
                    <button wire:click="$set('reviewModal', false)" 
                            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition">
                        Batal
                    </button>
                    <button wire:click="submitReview" 
                            class="px-4 py-2 text-white rounded-lg transition {{ $reviewAction === 'approved' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700' }}">
                        {{ $reviewAction === 'approved' ? 'Setujui' : 'Tolak' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
