<div class="space-y-6">
    <!-- Header Controls -->
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center space-x-4">
                <h2 class="text-2xl font-bold text-gray-900">Jadwal Interaktif</h2>
                <div class="flex items-center space-x-2">
                    <button wire:click="previousPeriod" class="p-2 hover:bg-gray-100 rounded-lg transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>
                    <button wire:click="goToToday" class="px-3 py-1 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition">
                        Hari Ini
                    </button>
                    <button wire:click="nextPeriod" class="p-2 hover:bg-gray-100 rounded-lg transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div class="flex items-center space-x-4">
                <!-- View Mode Selector -->
                <div class="flex bg-gray-100 rounded-lg p-1">
                    <button wire:click="setViewMode('month')" 
                            class="px-3 py-1 rounded {{ $viewMode === 'month' ? 'bg-white shadow' : '' }} transition">
                        Bulan
                    </button>
                    <button wire:click="setViewMode('week')" 
                            class="px-3 py-1 rounded {{ $viewMode === 'week' ? 'bg-white shadow' : '' }} transition">
                        Minggu
                    </button>
                    <button wire:click="setViewMode('day')" 
                            class="px-3 py-1 rounded {{ $viewMode === 'day' ? 'bg-white shadow' : '' }} transition">
                        Hari
                    </button>
                </div>

                <!-- Actions -->
                <button wire:click="exportSchedule" class="btn btn-white">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Export
                </button>
            </div>
        </div>

        <!-- Current Period Display -->
        <div class="text-center text-lg font-semibold text-gray-700">
            @switch($viewMode)
                @case('month')
                    {{ $currentDate->locale('id')->isoFormat('MMMM YYYY') }}
                    @break
                @case('week')
                    {{ $currentDate->copy()->startOfWeek()->locale('id')->isoFormat('D MMMM') }} - 
                    {{ $currentDate->copy()->endOfWeek()->locale('id')->isoFormat('D MMMM YYYY') }}
                    @break
                @case('day')
                    {{ $currentDate->locale('id')->isoFormat('dddd, D MMMM YYYY') }}
                    @break
            @endswitch
        </div>
    </div>

    <!-- User Filter -->
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center space-x-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-2">Filter User</label>
                <div class="relative">
                    <input type="text" 
                           wire:model.live="searchUser" 
                           placeholder="Cari user..."
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <svg class="absolute right-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>
            
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-2">User Dipilih</label>
                <div class="flex flex-wrap gap-2 min-h-[40px] p-2 border border-gray-300 rounded-lg">
                    @foreach($selectedUsers as $userId)
                        @php
                            $user = $availableUsers->where('id', $userId)->first();
                        @endphp
                        @if($user)
                            <span class="inline-flex items-center px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
                                {{ $user->name }}
                                <button wire:click="removeUser({{ $userId }})" class="ml-1 hover:text-blue-600">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </span>
                        @endif
                    @endforeach
                </div>
            </div>

            <div class="flex-1 max-w-xs">
                <label class="block text-sm font-medium text-gray-700 mb-2">Available Users</label>
                <div class="max-h-32 overflow-y-auto border border-gray-300 rounded-lg p-2">
                    @foreach($availableUsers as $user)
                        <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded cursor-pointer">
                            <input type="checkbox" 
                                   wire:model.live="selectedUsers" 
                                   value="{{ $user->id }}"
                                   class="rounded text-blue-600 focus:ring-blue-500">
                            <span class="text-sm">{{ $user->name }} ({{ $user->nim }})</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Calendar Grid -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        @switch($viewMode)
            @case('month')
                @include('livewire.schedule.calendar-month')
                @break
            @case('week')
                @include('livewire.schedule.calendar-week')
                @break
            @case('day')
                @include('livewire.schedule.calendar-day')
                @break
        @endswitch
    </div>

    <!-- Statistics -->
    <div class="bg-white rounded-lg shadow p-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Statistik Jadwal</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="text-center">
                <div class="text-2xl font-bold text-blue-600">{{ $this->getScheduleStats()['total'] }}</div>
                <div class="text-sm text-gray-600">Total Jadwal</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-green-600">{{ $this->getScheduleStats()['completed'] }}</div>
                <div class="text-sm text-gray-600">Selesai</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-yellow-600">{{ $this->getScheduleStats()['pending'] }}</div>
                <div class="text-sm text-gray-600">Pending</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-blue-600">{{ $this->getScheduleStats()['users_with_schedule'] }}</div>
                <div class="text-sm text-gray-600">User Aktif</div>
            </div>
        </div>
    </div>
</div>

<!-- Conflict Modal -->
@if($showConflictModal)
<div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <div class="flex items-center mb-4">
            <svg class="w-6 h-6 text-yellow-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
            </svg>
            <h3 class="text-lg font-semibold text-gray-900">Konflik Jadwal</h3>
        </div>
        
        <p class="text-gray-600 mb-4">
            Jadwal untuk <strong>{{ $conflictDetails['user'] }}</strong> pada 
            {{ $conflictDetails['date'] }} sesi {{ $conflictDetails['session'] }} 
            sudah ada. Apakah Anda ingin mengganti jadwal yang ada?
        </p>

        <div class="flex justify-end space-x-3">
            <button wire:click="cancelConflictMove" 
                    class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition">
                Batal
            </button>
            <button wire:click="confirmMoveWithConflict" 
                    class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition">
                Ganti Jadwal
            </button>
        </div>
    </div>
</div>
@endif

<!-- Assign Schedule Modal -->
@if($showAssignModal)
<div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Tetapkan Jadwal</h3>
        
        <form wire:submit.prevent="assignSchedule">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal</label>
                    <input type="date" 
                           wire:model="selectedDate" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sesi</label>
                    <select wire:model="selectedSession" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            required>
                        <option value="">Pilih Sesi</option>
                        <option value="1">Sesi 1 (07:30 - 10:00)</option>
                        <option value="2">Sesi 2 (10:20 - 12:50)</option>
                        <option value="3">Sesi 3 (13:30 - 16:00)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">User</label>
                    <div class="max-h-32 overflow-y-auto border border-gray-300 rounded-lg p-2">
                        @foreach($availableUsers as $user)
                            <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded cursor-pointer">
                                <input type="checkbox" 
                                       wire:model="selectedUsers" 
                                       value="{{ $user->id }}"
                                       class="rounded text-blue-600 focus:ring-blue-500">
                                <span class="text-sm">{{ $user->name }} ({{ $user->nim }})</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" 
                        wire:click="$set('showAssignModal', false)"
                        class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition">
                    Batal
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Tetapkan
                </button>
            </div>
        </form>
    </div>
</div>
@endif

<!-- Event Listeners for Drag & Drop -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize drag and drop
    @this.on('dragStarted', (data) => {
        console.log('Drag started:', data);
    });

    @this.on('scheduleMoved', (message) => {
        // Show success notification
        window.dispatchEvent(new CustomEvent('alert', {
            detail: { type: 'success', message: message }
        }));
    });

    @this.on('error', (message) => {
        // Show error notification
        window.dispatchEvent(new CustomEvent('alert', {
            detail: { type: 'error', message: message }
        }));
    });
});
</script>
