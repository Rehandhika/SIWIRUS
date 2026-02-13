<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Riwayat Perubahan Jadwal</h1>
                <p class="text-gray-600 mt-1">
                    Minggu: {{ $schedule->week_start_date->format('d M Y') }} - {{ $schedule->week_end_date->format('d M Y') }}
                </p>
            </div>
            <a href="{{ route('admin.schedule.edit', $schedule) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i>
                Kembali ke Edit
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Search -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cari</label>
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="search"
                    placeholder="Cari alasan atau editor..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                >
            </div>

            <!-- Action Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Aksi</label>
                <select 
                    wire:model.live="filterAction"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                >
                    <option value="all">Semua Aksi</option>
                    <option value="created">Ditambahkan</option>
                    <option value="updated">Diperbarui</option>
                    <option value="deleted">Dihapus</option>
                    <option value="bulk_created">Bulk Ditambahkan</option>
                    <option value="bulk_deleted">Bulk Dihapus</option>
                </select>
            </div>

            <!-- Editor Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Editor</label>
                <select 
                    wire:model.live="filterEditor"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                >
                    <option value="">Semua Editor</option>
                    @foreach($editors as $editor)
                        <option value="{{ $editor->id }}">{{ $editor->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Reset Button -->
            <div class="flex items-end">
                <button 
                    wire:click="resetFilters"
                    class="w-full px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition"
                >
                    <i class="fas fa-redo mr-2"></i>
                    Reset Filter
                </button>
            </div>
        </div>
    </div>

    <!-- History List -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        @if($history->isEmpty())
            <div class="p-8 text-center text-gray-500">
                <i class="fas fa-history text-4xl mb-3"></i>
                <p>Tidak ada riwayat perubahan</p>
            </div>
        @else
            <div class="divide-y divide-gray-200">
                @foreach($history as $item)
                    <div class="p-4 hover:bg-gray-50 transition">
                        <div class="flex items-start justify-between">
                            <!-- Main Info -->
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <!-- Action Badge -->
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full
                                        @if($item->action === 'created' || $item->action === 'bulk_created')
                                            bg-green-100 text-green-800
                                        @elseif($item->action === 'updated')
                                            bg-blue-100 text-blue-800
                                        @elseif($item->action === 'deleted' || $item->action === 'bulk_deleted')
                                            bg-red-100 text-red-800
                                        @else
                                            bg-gray-100 text-gray-800
                                        @endif
                                    ">
                                        @if($item->action === 'created')
                                            <i class="fas fa-plus mr-1"></i> Ditambahkan
                                        @elseif($item->action === 'updated')
                                            <i class="fas fa-edit mr-1"></i> Diperbarui
                                        @elseif($item->action === 'deleted')
                                            <i class="fas fa-trash mr-1"></i> Dihapus
                                        @elseif($item->action === 'bulk_created')
                                            <i class="fas fa-plus-circle mr-1"></i> Bulk Ditambahkan
                                        @elseif($item->action === 'bulk_deleted')
                                            <i class="fas fa-trash-alt mr-1"></i> Bulk Dihapus
                                        @else
                                            {{ $item->action }}
                                        @endif
                                    </span>

                                    <!-- Editor -->
                                    <span class="text-sm text-gray-600">
                                        oleh <strong>{{ $item->editor->name ?? 'System' }}</strong>
                                    </span>

                                    <!-- Timestamp -->
                                    <span class="text-sm text-gray-500">
                                        {{ $item->created_at->diffForHumans() }}
                                    </span>
                                </div>

                                <!-- Details -->
                                <div class="text-sm text-gray-700 mb-2">
                                    @if($item->new_values)
                                        @if(isset($item->new_values['date']) && isset($item->new_values['session']))
                                            <span class="font-medium">
                                                {{ \Carbon\Carbon::parse($item->new_values['date'])->format('d M Y') }}, 
                                                Sesi {{ $item->new_values['session'] }}
                                            </span>
                                        @endif

                                        @if(isset($item->new_values['user_name']))
                                            - User: <strong>{{ $item->new_values['user_name'] }}</strong>
                                        @endif

                                        @if(isset($item->new_values['count']))
                                            - <strong>{{ $item->new_values['count'] }}</strong> assignment
                                        @endif
                                    @endif

                                    @if($item->old_values && isset($item->old_values['user_name']))
                                        <div class="mt-1 text-gray-500">
                                            Sebelumnya: {{ $item->old_values['user_name'] }}
                                        </div>
                                    @endif
                                </div>

                                <!-- Reason -->
                                @if($item->reason)
                                    <div class="text-sm text-gray-600 italic">
                                        <i class="fas fa-comment-alt mr-1"></i>
                                        {{ $item->reason }}
                                    </div>
                                @endif
                            </div>

                            <!-- Assignment ID -->
                            @if($item->assignment_id)
                                <div class="text-xs text-gray-400">
                                    #{{ $item->assignment_id }}
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $history->links() }}
            </div>
        @endif
    </div>

    <!-- Summary Stats -->
    <div class="mt-6 grid grid-cols-1 md:grid-cols-5 gap-4">
        @php
            $totalHistory = AssignmentEditHistory::where('schedule_id', $schedule->id)->count();
            $created = AssignmentEditHistory::where('schedule_id', $schedule->id)->where('action', 'created')->count();
            $updated = AssignmentEditHistory::where('schedule_id', $schedule->id)->where('action', 'updated')->count();
            $deleted = AssignmentEditHistory::where('schedule_id', $schedule->id)->where('action', 'deleted')->count();
            $bulk = AssignmentEditHistory::where('schedule_id', $schedule->id)->whereIn('action', ['bulk_created', 'bulk_deleted'])->count();
        @endphp

        <div class="bg-white rounded-lg shadow-sm p-4 text-center">
            <div class="text-2xl font-bold text-gray-900">{{ $totalHistory }}</div>
            <div class="text-sm text-gray-600">Total Perubahan</div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-4 text-center">
            <div class="text-2xl font-bold text-green-600">{{ $created }}</div>
            <div class="text-sm text-gray-600">Ditambahkan</div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-4 text-center">
            <div class="text-2xl font-bold text-blue-600">{{ $updated }}</div>
            <div class="text-sm text-gray-600">Diperbarui</div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-4 text-center">
            <div class="text-2xl font-bold text-red-600">{{ $deleted }}</div>
            <div class="text-sm text-gray-600">Dihapus</div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-4 text-center">
            <div class="text-2xl font-bold text-blue-600">{{ $bulk }}</div>
            <div class="text-sm text-gray-600">Bulk Operations</div>
        </div>
    </div>
</div>
