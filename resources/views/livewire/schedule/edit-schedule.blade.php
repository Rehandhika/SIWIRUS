<div class="min-h-screen">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl shadow-lg p-4 lg:p-6 text-white mb-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl lg:text-2xl font-bold">Edit Jadwal</h1>
                <p class="text-blue-100 text-sm mt-1">
                    {{ \Carbon\Carbon::parse($schedule->week_start_date)->locale('id')->isoFormat('D MMMM') }} - 
                    {{ \Carbon\Carbon::parse($schedule->week_start_date)->addDays(3)->locale('id')->isoFormat('D MMMM YYYY') }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                @if($hasUnsavedChanges)
                    <span class="px-3 py-1.5 bg-yellow-500 rounded-lg text-xs font-semibold flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"/>
                        </svg>
                        {{ count($changes) }} perubahan
                    </span>
                @endif
                <button wire:click="saveChanges" wire:loading.attr="disabled"
                    class="px-4 py-2 bg-white text-blue-600 rounded-lg text-sm font-semibold hover:bg-blue-50 disabled:opacity-50 transition-colors shadow-sm">
                    <span wire:loading.remove wire:target="saveChanges">Simpan</span>
                    <span wire:loading wire:target="saveChanges">Menyimpan...</span>
                </button>
                <a href="{{ route('admin.schedule.index') }}" 
                   class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg text-sm font-medium transition-colors">
                    Kembali
                </a>
            </div>
        </div>
    </div>

    <!-- Schedule Grid -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <!-- Mobile scroll hint -->
        <div class="lg:hidden px-4 py-2 bg-blue-50 border-b border-blue-100 flex items-center justify-between text-xs text-blue-600">
            <span>← Geser untuk melihat semua hari →</span>
            <svg class="w-4 h-4 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
            </svg>
        </div>
        
        <div class="overflow-x-auto" style="-webkit-overflow-scrolling: touch;">
            <table class="w-full" style="min-width: 700px;">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="p-3 lg:p-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-28 lg:w-36 sticky left-0 bg-gray-50 z-10 border-r border-gray-200">
                            Sesi
                        </th>
                        @foreach($this->getScheduleDates() as $d)
                            <th class="p-3 lg:p-4 text-center min-w-[140px] lg:min-w-[180px]">
                                <div class="text-blue-600 font-semibold text-sm">{{ $d['day_name'] }}</div>
                                <div class="text-gray-900 text-xs mt-0.5">{{ $d['formatted'] }}</div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @php
                        $sessionColors = [
                            1 => ['bg' => 'bg-blue-50', 'border' => 'border-l-blue-500', 'badge' => 'bg-blue-100 text-blue-700'],
                            2 => ['bg' => 'bg-emerald-50', 'border' => 'border-l-emerald-500', 'badge' => 'bg-emerald-100 text-emerald-700'],
                            3 => ['bg' => 'bg-blue-50', 'border' => 'border-l-blue-500', 'badge' => 'bg-blue-100 text-blue-700'],
                        ];
                    @endphp
                    @foreach([1, 2, 3] as $session)
                        @php $colors = $sessionColors[$session]; @endphp
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="p-3 lg:p-4 sticky left-0 bg-white z-10 border-r border-gray-200 {{ $colors['bg'] }} border-l-4 {{ $colors['border'] }}">
                                <div class="flex items-center gap-2">
                                    <span class="w-8 h-8 rounded-lg {{ $colors['badge'] }} flex items-center justify-center font-bold text-sm">
                                        {{ $session }}
                                    </span>
                                    <div>
                                        <div class="font-semibold text-gray-900 text-sm">Sesi {{ $session }}</div>
                                        <div class="text-xs text-gray-500">{{ $this->getSessionTime($session)['start'] }} - {{ $this->getSessionTime($session)['end'] }}</div>
                                    </div>
                                </div>
                            </td>
                            @foreach($this->getScheduleDates() as $d)
                                @php
                                    $date = $d['date'];
                                    $users = $assignments[$date][$session] ?? [];
                                    $count = count($users);
                                    $full = $count >= $maxUsersPerSlot;
                                    $slotClass = $count > 0 
                                        ? 'bg-green-50 border-green-200 hover:border-green-300' 
                                        : 'bg-gray-50 border-gray-200 hover:border-gray-300';
                                @endphp
                                <td class="p-2 lg:p-3">
                                    <div class="border-2 rounded-xl p-2 lg:p-3 min-h-[120px] lg:min-h-[140px] {{ $slotClass }} transition-colors">
                                        <!-- Header -->
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $count > 0 ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                                {{ $count }} user
                                            </span>
                                            @if($full)
                                                <span class="text-xs text-orange-600 font-medium">Penuh</span>
                                            @endif
                                        </div>
                                        
                                        <!-- User List -->
                                        <div class="space-y-1 mb-2">
                                            @forelse(array_slice($users, 0, 3) as $u)
                                                <div class="flex items-center justify-between bg-white rounded-lg px-2 py-1.5 shadow-sm border border-gray-100 group">
                                                    <div class="flex items-center gap-1.5 min-w-0">
                                                        <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center text-[10px] font-semibold text-blue-700 shrink-0">
                                                            {{ strtoupper(substr($u['user_name'], 0, 1)) }}
                                                        </div>
                                                        <span class="text-xs text-gray-700 truncate">{{ $u['user_name'] }}</span>
                                                    </div>
                                                    <button wire:click="removeUserFromSlot({{ $u['id'] }})" 
                                                            wire:loading.attr="disabled"
                                                            class="opacity-0 group-hover:opacity-100 text-red-400 hover:text-red-600 p-1 hover:bg-red-50 rounded transition-all"
                                                            title="Hapus">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                            @empty
                                                <div class="text-xs text-gray-400 text-center py-3">Belum ada user</div>
                                            @endforelse
                                            @if($count > 3)
                                                <div class="text-xs text-gray-500 text-center py-1">+{{ $count - 3 }} lainnya</div>
                                            @endif
                                        </div>
                                        
                                        <!-- Add Button -->
                                        <button wire:click="openUserSelector('{{ $date }}', {{ $session }})"
                                                wire:loading.attr="disabled"
                                                @if($full) disabled @endif
                                                class="w-full py-2 text-xs font-medium rounded-lg transition-all
                                                       {{ $full 
                                                          ? 'bg-gray-200 text-gray-400 cursor-not-allowed' 
                                                          : 'bg-blue-600 text-white hover:bg-blue-700 active:bg-blue-800 shadow-sm hover:shadow' }}">
                                            <span class="flex items-center justify-center gap-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                                </svg>
                                                Tambah User
                                            </span>
                                        </button>
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- User Selector Modal -->
    @if($showUserSelector && $selectedSlot)
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4" 
             wire:click.self="closeUserSelector"
             x-data x-transition>
            <div class="bg-white rounded-2xl w-full max-w-md max-h-[85vh] overflow-hidden shadow-2xl" 
                 wire:click.stop
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100">
                
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-4">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-semibold text-lg">Tambah User</h3>
                            <p class="text-blue-100 text-sm mt-0.5">
                                {{ \Carbon\Carbon::parse($selectedSlot['date'])->locale('id')->isoFormat('dddd, D MMMM') }}
                            </p>
                            <p class="text-blue-200 text-xs">Sesi {{ $selectedSlot['session'] }} • {{ $this->getSessionTime($selectedSlot['session'])['start'] }} - {{ $this->getSessionTime($selectedSlot['session'])['end'] }}</p>
                        </div>
                        <button wire:click="closeUserSelector" 
                                class="p-2 hover:bg-white/20 rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- Search -->
                <div class="p-4 border-b border-gray-100">
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" 
                               wire:model.live.debounce.300ms="searchTerm" 
                               placeholder="Cari nama user..." 
                               class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all">
                    </div>
                </div>
                
                <!-- User List -->
                <div class="max-h-[50vh] overflow-y-auto p-2">
                    @forelse($availableUsers as $user)
                        <button wire:click="addUserToSlot('{{ $selectedSlot['date'] }}', {{ $selectedSlot['session'] }}, {{ $user->id }})"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50"
                                class="w-full flex items-center gap-3 p-3 hover:bg-blue-50 rounded-xl text-left transition-colors group">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center text-white font-semibold text-sm shadow-sm">
                                {{ strtoupper(substr($user->name, 0, 2)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-gray-900 truncate">{{ $user->name }}</div>
                                <div class="text-xs text-gray-500">Klik untuk menambahkan</div>
                            </div>
                            <svg class="w-5 h-5 text-blue-500 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                        </button>
                    @empty
                        <div class="text-center py-8">
                            <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <p class="text-gray-500 font-medium">Tidak ada user ditemukan</p>
                            <p class="text-gray-400 text-sm mt-1">Coba kata kunci lain</p>
                        </div>
                    @endforelse
                </div>
                
                <!-- Modal Footer -->
                <div class="p-4 border-t border-gray-100 bg-gray-50">
                    <button wire:click="closeUserSelector"
                            class="w-full py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Loading Overlay -->
    <div wire:loading.flex wire:target="addUserToSlot, removeUserFromSlot, saveChanges" 
         class="fixed inset-0 bg-black/30 backdrop-blur-sm items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 shadow-2xl flex flex-col items-center gap-3">
            <svg class="animate-spin h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-sm font-medium text-gray-700">Memproses...</span>
        </div>
    </div>

    <!-- Toast Notification -->
    <div x-data="{ show: false, message: '', type: 'success' }"
         x-cloak
         @notify.window="show = true; message = $event.detail.message; type = $event.detail.type || 'success'; setTimeout(() => show = false, 3000)"
         x-show="show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="fixed bottom-4 right-4 z-50">
        <div class="px-4 py-3 rounded-xl shadow-lg text-white text-sm font-medium flex items-center gap-2"
             :class="type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500'">
            <svg x-show="type === 'success'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <svg x-show="type === 'error'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            <span x-text="message"></span>
        </div>
    </div>

    <style>
        [x-cloak] { display: none !important; }
        
        /* Custom scrollbar */
        .overflow-x-auto::-webkit-scrollbar {
            height: 6px;
        }
        .overflow-x-auto::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 3px;
        }
        .overflow-x-auto::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        .overflow-x-auto::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</div>
