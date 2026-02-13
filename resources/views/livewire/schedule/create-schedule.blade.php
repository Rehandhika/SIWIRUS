<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
    {{-- Header --}}
    <div class="mb-4 sm:mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Buat Jadwal Baru</h1>
                <p class="mt-1 text-sm text-gray-600">Jadwal shift mingguan (Senin-Kamis)</p>
            </div>
            <x-ui.button href="{{ route('admin.schedule.index') }}" variant="secondary" icon="arrow-left" class="w-full sm:w-auto">
                Kembali
            </x-ui.button>
        </div>
    </div>

    {{-- Period Selection --}}
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 mb-4 sm:mb-6">
        <h2 class="text-base sm:text-lg font-semibold text-gray-900 mb-4">Periode Jadwal</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Mulai (Senin)</label>
                <input type="date" wire:model.live="weekStartDate" class="input w-full" required>
                @error('weekStartDate') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Selesai (Kamis)</label>
                <input type="date" wire:model.live="weekEndDate" class="input w-full" required>
                @error('weekEndDate') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Catatan (Opsional)</label>
                <input type="text" wire:model="notes" class="input w-full" placeholder="Catatan jadwal...">
            </div>
        </div>
    </div>

    {{-- Schedule Grid --}}
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 mb-4 sm:mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-3">
            <h2 class="text-base sm:text-lg font-semibold text-gray-900">Jadwal Shift</h2>
            <div class="flex items-center gap-2">
                <button wire:click="undo" 
                        {{ !$canUndo ? 'disabled' : '' }}
                        class="p-2 rounded-lg transition-colors {{ $canUndo ? 'text-gray-700 hover:bg-gray-100' : 'text-gray-300 cursor-not-allowed' }}"
                        title="Undo">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                    </svg>
                </button>
                <button wire:click="redo" 
                        {{ !$canRedo ? 'disabled' : '' }}
                        class="p-2 rounded-lg transition-colors {{ $canRedo ? 'text-gray-700 hover:bg-gray-100' : 'text-gray-300 cursor-not-allowed' }}"
                        title="Redo">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10h-10a8 8 0 00-8 8v2m18-10l-6 6m6-6l-6-6"/>
                    </svg>
                </button>
                @if($canUndo || $canRedo)
                <span class="text-xs text-gray-500">{{ $historyIndex + 1 }}/{{ count($history) }}</span>
                @endif
            </div>
        </div>
        
        {{-- Desktop View --}}
        <div class="hidden lg:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hari / Tanggal</th>
                        @for($session = 1; $session <= 3; $session++)
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            Sesi {{ $session }}<br>
                            <span class="text-xs font-normal text-gray-400">{{ $this->getSessionTime($session) }}</span>
                        </th>
                        @endfor
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @php $startDate = \Carbon\Carbon::parse($weekStartDate); @endphp
                    @for($day = 0; $day < 4; $day++)
                        @php
                            $date = $startDate->copy()->addDays($day);
                            $dateStr = $date->format('Y-m-d');
                            $dayName = $date->locale('id')->dayName;
                        @endphp
                        <tr>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $dayName }}</div>
                                <div class="text-xs text-gray-500">{{ $date->format('d M Y') }}</div>
                            </td>
                            @for($session = 1; $session <= 3; $session++)
                            <td class="px-4 py-4">
                                @php
                                    $slotAssignments = $this->getSlotAssignments($dateStr, $session);
                                    $userCount = count($slotAssignments);
                                @endphp
                                
                                @if($userCount > 0)
                                    <div class="space-y-2">
                                        @foreach($slotAssignments as $assignment)
                                        <div class="flex items-center justify-between p-2 rounded-lg border 
                                                    {{ $assignment['has_availability_warning'] ?? false ? 'bg-yellow-50 border-yellow-300' : 'bg-blue-50 border-blue-200' }}">
                                            <div class="flex items-center space-x-2 min-w-0 flex-1">
                                                @if($assignment['user_photo'])
                                                <img src="{{ asset('storage/' . $assignment['user_photo']) }}" 
                                                     alt="{{ $assignment['user_name'] }}" 
                                                     class="w-7 h-7 rounded-full flex-shrink-0">
                                                @else
                                                <div class="w-7 h-7 rounded-full bg-blue-600 flex items-center justify-center text-white text-xs font-medium flex-shrink-0">
                                                    {{ substr($assignment['user_name'], 0, 1) }}
                                                </div>
                                                @endif
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-center space-x-1">
                                                        <div class="text-sm font-medium text-gray-900 truncate">{{ $assignment['user_name'] }}</div>
                                                        @if($assignment['has_availability_warning'] ?? false)
                                                        <svg class="w-4 h-4 text-yellow-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="Tidak tersedia">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                                        </svg>
                                                        @endif
                                                    </div>
                                                    <div class="text-xs text-gray-500 truncate">{{ $assignment['user_nim'] }}</div>
                                                </div>
                                            </div>
                                            <button wire:click="removeUserFromSlot('{{ $dateStr }}', {{ $session }}, {{ $assignment['user_id'] }})" 
                                                    class="ml-2 p-1 text-red-600 hover:bg-red-100 rounded flex-shrink-0">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </div>
                                        @endforeach
                                        <button wire:click="selectCell('{{ $dateStr }}', {{ $session }})" 
                                                class="w-full p-2 border border-dashed border-gray-300 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-colors text-sm text-gray-600">
                                            + Tambah User
                                        </button>
                                    </div>
                                @else
                                    <button wire:click="selectCell('{{ $dateStr }}', {{ $session }})" 
                                            class="w-full p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-colors">
                                        <svg class="w-6 h-6 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        <span class="text-xs text-gray-500 mt-1 block">Assign</span>
                                    </button>
                                @endif
                            </td>
                            @endfor
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>

        {{-- Mobile/Tablet View --}}
        <div class="lg:hidden space-y-4">
            @php $startDate = \Carbon\Carbon::parse($weekStartDate); @endphp
            @for($day = 0; $day < 4; $day++)
                @php
                    $date = $startDate->copy()->addDays($day);
                    $dateStr = $date->format('Y-m-d');
                    $dayName = $date->locale('id')->dayName;
                @endphp
                <div class="border border-gray-200 rounded-lg overflow-hidden">
                    <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                        <div class="font-medium text-gray-900">{{ $dayName }}</div>
                        <div class="text-sm text-gray-500">{{ $date->format('d M Y') }}</div>
                    </div>
                    <div class="p-4 space-y-4">
                        @for($session = 1; $session <= 3; $session++)
                        <div>
                            <div class="text-sm font-medium text-gray-700 mb-2">
                                Sesi {{ $session }} - {{ $this->getSessionTime($session) }}
                            </div>
                            @php
                                $slotAssignments = $this->getSlotAssignments($dateStr, $session);
                                $userCount = count($slotAssignments);
                            @endphp
                            
                            @if($userCount > 0)
                                <div class="space-y-2">
                                    @foreach($slotAssignments as $assignment)
                                    <div class="flex items-center justify-between p-3 rounded-lg border 
                                                {{ $assignment['has_availability_warning'] ?? false ? 'bg-yellow-50 border-yellow-300' : 'bg-blue-50 border-blue-200' }}">
                                        <div class="flex items-center space-x-3 min-w-0 flex-1">
                                            @if($assignment['user_photo'])
                                            <img src="{{ asset('storage/' . $assignment['user_photo']) }}" 
                                                 alt="{{ $assignment['user_name'] }}" 
                                                 class="w-10 h-10 rounded-full flex-shrink-0">
                                            @else
                                            <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-medium flex-shrink-0">
                                                {{ substr($assignment['user_name'], 0, 1) }}
                                            </div>
                                            @endif
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center space-x-2">
                                                    <div class="text-sm font-medium text-gray-900 truncate">{{ $assignment['user_name'] }}</div>
                                                    @if($assignment['has_availability_warning'] ?? false)
                                                    <svg class="w-4 h-4 text-yellow-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                                    </svg>
                                                    @endif
                                                </div>
                                                <div class="text-xs text-gray-500 truncate">{{ $assignment['user_nim'] }}</div>
                                            </div>
                                        </div>
                                        <button wire:click="removeUserFromSlot('{{ $dateStr }}', {{ $session }}, {{ $assignment['user_id'] }})" 
                                                class="ml-2 p-2 text-red-600 hover:bg-red-100 rounded flex-shrink-0">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                    @endforeach
                                    <button wire:click="selectCell('{{ $dateStr }}', {{ $session }})" 
                                            class="w-full p-2 border border-dashed border-gray-300 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-colors text-sm text-gray-600">
                                        + Tambah User
                                    </button>
                                </div>
                            @else
                                <button wire:click="selectCell('{{ $dateStr }}', {{ $session }})" 
                                        class="w-full p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-colors">
                                    <svg class="w-6 h-6 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    <span class="text-xs text-gray-500 mt-1 block">Assign User</span>
                                </button>
                            @endif
                        </div>
                        @endfor
                    </div>
                </div>
            @endfor
        </div>
    </div>

    {{-- Conflicts Alert --}}
    @if(!empty($conflicts['critical']) || !empty($conflicts['warning']))
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 mb-4 sm:mb-6">
        <h2 class="text-base sm:text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            Konflik Terdeteksi
        </h2>
        
        @if(!empty($conflicts['critical']))
        <div class="mb-4">
            <h3 class="text-sm font-medium text-red-700 mb-2">Critical Issues</h3>
            <div class="space-y-2">
                @foreach($conflicts['critical'] as $conflict)
                <div class="flex items-start p-3 bg-red-50 border border-red-200 rounded-lg">
                    <svg class="w-5 h-5 text-red-600 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    <p class="text-sm text-red-800">{{ $conflict['message'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
        @endif
        
        @if(!empty($conflicts['warning']))
        <div>
            <h3 class="text-sm font-medium text-yellow-700 mb-2">Warnings</h3>
            <div class="space-y-2">
                @foreach($conflicts['warning'] as $conflict)
                <div class="flex items-start p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <svg class="w-5 h-5 text-yellow-600 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <p class="text-sm text-yellow-800">{{ $conflict['message'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- Statistics --}}
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 mb-4 sm:mb-6">
        <h2 class="text-base sm:text-lg font-semibold text-gray-900 mb-4">Statistik</h2>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <div class="bg-blue-50 rounded-lg p-3 sm:p-4">
                <div class="text-xs sm:text-sm text-blue-600 font-medium">Total Assignments</div>
                <div class="text-xl sm:text-2xl font-bold text-blue-900 mt-1">{{ $totalAssignments }}</div>
                <div class="text-xs text-blue-700 mt-1">dari 12 slot</div>
            </div>
            <div class="bg-green-50 rounded-lg p-3 sm:p-4">
                <div class="text-xs sm:text-sm text-green-600 font-medium">Coverage Rate</div>
                <div class="text-xl sm:text-2xl font-bold text-green-900 mt-1">{{ number_format($coverageRate, 1) }}%</div>
                <div class="text-xs text-green-700 mt-1">slot terisi</div>
            </div>
            <div class="bg-blue-50 rounded-lg p-3 sm:p-4">
                <div class="text-xs sm:text-sm text-blue-600 font-medium">Unique Users</div>
                <div class="text-xl sm:text-2xl font-bold text-blue-900 mt-1">{{ count($assignmentsPerUser) }}</div>
                <div class="text-xs text-blue-700 mt-1">anggota</div>
            </div>
            <div class="bg-orange-50 rounded-lg p-3 sm:p-4">
                <div class="text-xs sm:text-sm text-orange-600 font-medium">Empty Slots</div>
                <div class="text-xl sm:text-2xl font-bold text-orange-900 mt-1">{{ $emptySlots }}</div>
                <div class="text-xs text-orange-700 mt-1">slot kosong</div>
            </div>
        </div>
        
        @if(!empty($assignmentsPerUser))
        <div class="mt-4 sm:mt-6">
            <h3 class="text-sm font-medium text-gray-700 mb-3">Distribusi Beban Kerja</h3>
            <div class="space-y-2">
                @foreach($assignmentsPerUser as $userStat)
                <div class="flex items-center justify-between gap-3">
                    <span class="text-sm text-gray-600 truncate flex-shrink min-w-0">{{ $userStat['user_name'] }}</span>
                    <div class="flex items-center space-x-2 flex-shrink-0">
                        <div class="w-24 sm:w-32 bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ min(($userStat['count'] / 4) * 100, 100) }}%"></div>
                        </div>
                        <span class="text-sm font-medium text-gray-900 w-8 text-right">{{ $userStat['count'] }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- Actions --}}
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-3">
        <x-ui.button 
            wire:click.prevent="saveDraft" 
            variant="secondary"
            icon="save"
            :disabled="$isSaving"
            wire:loading.attr="disabled"
            wire:target="saveDraft,publish"
            class="w-full sm:w-auto"
            type="button">
            <span wire:loading.remove wire:target="saveDraft">Simpan Draft</span>
            <span wire:loading wire:target="saveDraft">Menyimpan...</span>
        </x-ui.button>
        
        <x-ui.button 
            wire:click.prevent="publish" 
            variant="primary"
            icon="check-circle"
            :disabled="$isSaving"
            wire:loading.attr="disabled"
            wire:target="saveDraft,publish"
            class="w-full sm:w-auto"
            type="button">
            <span wire:loading.remove wire:target="publish">Publish Jadwal</span>
            <span wire:loading wire:target="publish">Publishing...</span>
        </x-ui.button>
    </div>

    {{-- User Selector Modal --}}
    @if($showUserSelector)
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50 p-4" wire:click.self="$set('showUserSelector', false)">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-hidden">
            <div class="px-4 sm:px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="min-w-0 flex-1">
                        <h3 class="text-base sm:text-lg font-semibold text-gray-900">Pilih Anggota</h3>
                        <p class="text-sm text-gray-600 mt-1 truncate">
                            {{ \Carbon\Carbon::parse($selectedDate)->locale('id')->dayName }}, 
                            {{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }} - 
                            Sesi {{ $selectedSession }} ({{ $this->getSessionTime($selectedSession) }})
                        </p>
                    </div>
                    <button wire:click="$set('showUserSelector', false)" class="ml-4 text-gray-400 hover:text-gray-600 flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div class="px-4 sm:px-6 py-4 max-h-[calc(90vh-140px)] overflow-y-auto">
                @if(empty($availableUsers))
                <div class="text-center py-8 text-gray-500">
                    <svg class="w-12 h-12 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <p class="text-sm">Tidak ada user yang tersedia</p>
                </div>
                @else
                <div class="space-y-2">
                    @foreach($availableUsers as $user)
                    <button wire:click="assignUser({{ $user['id'] }})" 
                            class="w-full flex items-center justify-between p-3 sm:p-4 rounded-lg border transition-colors
                                   {{ $user['has_conflict'] ? 'border-red-200 bg-red-50 cursor-not-allowed' : 
                                      ($user['is_not_available'] ? 'border-yellow-200 bg-yellow-50 hover:bg-yellow-100' :
                                       ($user['is_available'] ? 'border-green-200 bg-green-50 hover:bg-green-100' : 
                                        'border-gray-200 bg-gray-50 hover:bg-gray-100')) }}"
                            {{ $user['has_conflict'] ? 'disabled' : '' }}>
                        <div class="flex items-center space-x-3 min-w-0 flex-1">
                            @if($user['photo'])
                            <img src="{{ asset('storage/' . $user['photo']) }}" 
                                 alt="{{ $user['name'] }}" 
                                 class="w-10 h-10 rounded-full flex-shrink-0">
                            @else
                            <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-medium flex-shrink-0">
                                {{ substr($user['name'], 0, 1) }}
                            </div>
                            @endif
                            <div class="text-left min-w-0 flex-1">
                                <div class="font-medium text-gray-900 truncate">{{ $user['name'] }}</div>
                                <div class="text-sm text-gray-500 truncate">{{ $user['nim'] }}</div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2 flex-shrink-0 ml-2">
                            <span class="text-sm text-gray-600 hidden sm:inline">{{ $user['current_assignments'] }} shifts</span>
                            @if($user['has_conflict'])
                            <x-ui.badge variant="danger" size="sm">Conflict</x-ui.badge>
                            @elseif($user['is_not_available'])
                            <x-ui.badge variant="warning" size="sm">Not Available</x-ui.badge>
                            @elseif($user['is_available'])
                            <x-ui.badge variant="success" size="sm">Available</x-ui.badge>
                            @else
                            <x-ui.badge variant="gray" size="sm">No Data</x-ui.badge>
                            @endif
                        </div>
                    </button>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Loading Overlay --}}
    <div wire:loading wire:target="saveDraft,publish" 
         class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl p-6 max-w-sm mx-4">
            <div class="flex items-center space-x-4">
                <svg class="animate-spin h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <div>
                    <div class="text-lg font-semibold text-gray-900">Processing...</div>
                    <div class="text-sm text-gray-600">Please wait</div>
                </div>
            </div>
        </div>
    </div>
</div>
