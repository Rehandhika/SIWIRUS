<div class="space-y-6">
    <x-layout.page-header 
        title="Pengaturan Toko"
        description="Kelola status operasional koperasi dan konfigurasi Poin SHU"
    />

    {{-- Current Status Section --}}
    <x-ui.card>
        <x-layout.form-section 
            title="Status Saat Ini"
            description="Status operasional koperasi secara real-time"
        >
            @if($statusLoaded)
                <div class="space-y-3">
                    {{-- Status Badge --}}
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            @if($currentStatus['is_open'])
                                <span class="flex h-3 w-3 relative">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                                </span>
                                <span class="text-lg font-semibold text-green-600">BUKA</span>
                            @else
                                <span class="flex h-3 w-3"><span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span></span>
                                <span class="text-lg font-semibold text-red-600">TUTUP</span>
                            @endif

                            {{-- Mode Badge --}}
                            @if($currentStatus['mode'] === 'manual')
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-700">Manual</span>
                            @elseif($currentStatus['mode'] === 'override')
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-700">Override</span>
                            @endif
                        </div>
                        <button wire:click="refreshStatus" class="text-gray-400 hover:text-gray-600" title="Refresh">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </button>
                    </div>

                    {{-- Info --}}
                    <div class="text-sm text-gray-600">{{ $currentStatus['reason'] }}</div>

                    {{-- Academic Holiday --}}
                    @if(!empty($currentStatus['academic_holiday']))
                        <div class="flex items-center gap-2 text-sm text-blue-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span>{{ $currentStatus['academic_holiday']['name'] }} ({{ $currentStatus['academic_holiday']['formatted_period'] }})</span>
                        </div>
                    @endif

                    {{-- Attendees --}}
                    @if($currentStatus['is_open'] && !empty($currentStatus['attendees']))
                        <div class="flex flex-wrap gap-1">
                            @foreach($currentStatus['attendees'] as $attendee)
                                <span class="px-2 py-0.5 text-xs rounded bg-green-50 text-green-700 border border-green-200">{{ $attendee }}</span>
                            @endforeach
                        </div>
                    @endif

                    {{-- Next Open --}}
                    @if(!$currentStatus['is_open'] && $currentStatus['next_open_time'])
                        <div class="text-sm"><span class="text-gray-500">Buka:</span> <span class="font-medium">{{ $currentStatus['next_open_time'] }}</span></div>
                    @endif
                </div>
            @else
                <div class="text-center py-4 text-gray-500">Memuat...</div>
            @endif
        </x-layout.form-section>
    </x-ui.card>

    {{-- Control Section (Simplified) --}}
    <x-ui.card>
        <x-layout.form-section 
            title="Kontrol Status"
            description="Atur mode operasional koperasi"
        >
            <div class="space-y-4">
                {{-- Mode Buttons --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    {{-- Auto Mode --}}
                    <button 
                        wire:click="resetToAuto"
                        wire:confirm="Kembali ke mode otomatis?"
                        class="p-4 rounded-lg border-2 transition-all {{ $currentStatus['mode'] === 'auto' ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:border-gray-300' }}"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full {{ $currentStatus['mode'] === 'auto' ? 'bg-green-100' : 'bg-gray-100' }} flex items-center justify-center">
                                <svg class="w-4 h-4 {{ $currentStatus['mode'] === 'auto' ? 'text-green-600' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="text-left">
                                <p class="font-medium text-sm {{ $currentStatus['mode'] === 'auto' ? 'text-green-700' : 'text-gray-700' }}">Otomatis</p>
                                <p class="text-xs text-gray-500">Ikuti jadwal & kehadiran</p>
                            </div>
                        </div>
                    </button>

                    {{-- Override Mode --}}
                    <button 
                        wire:click="{{ $currentStatus['mode'] === 'override' ? 'disableOpenOverride' : 'enableOpenOverride' }}"
                        wire:confirm="{{ $currentStatus['mode'] === 'override' ? 'Nonaktifkan override?' : 'Aktifkan override? Koperasi bisa buka di luar jadwal jika ada pengurus.' }}"
                        class="p-4 rounded-lg border-2 transition-all {{ $currentStatus['mode'] === 'override' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300' }}"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full {{ $currentStatus['mode'] === 'override' ? 'bg-blue-100' : 'bg-gray-100' }} flex items-center justify-center">
                                <svg class="w-4 h-4 {{ $currentStatus['mode'] === 'override' ? 'text-blue-600' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div class="text-left">
                                <p class="font-medium text-sm {{ $currentStatus['mode'] === 'override' ? 'text-blue-700' : 'text-gray-700' }}">Override</p>
                                <p class="text-xs text-gray-500">Buka di luar jadwal</p>
                            </div>
                        </div>
                    </button>

                    {{-- Manual Mode --}}
                    <button 
                        wire:click="{{ $currentStatus['mode'] === 'manual' ? 'disableManualMode' : 'enableManualMode' }}"
                        wire:confirm="{{ $currentStatus['mode'] === 'manual' ? 'Nonaktifkan mode manual?' : 'Aktifkan mode manual? Anda akan kontrol penuh status.' }}"
                        class="p-4 rounded-lg border-2 transition-all {{ $currentStatus['mode'] === 'manual' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300' }}"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full {{ $currentStatus['mode'] === 'manual' ? 'bg-blue-100' : 'bg-gray-100' }} flex items-center justify-center">
                                <svg class="w-4 h-4 {{ $currentStatus['mode'] === 'manual' ? 'text-blue-600' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                                </svg>
                            </div>
                            <div class="text-left">
                                <p class="font-medium text-sm {{ $currentStatus['mode'] === 'manual' ? 'text-blue-700' : 'text-gray-700' }}">Manual</p>
                                <p class="text-xs text-gray-500">Kontrol penuh admin</p>
                            </div>
                        </div>
                    </button>
                </div>

                {{-- Manual Controls --}}
                @if($currentStatus['mode'] === 'manual')
                    <div class="flex gap-3 pt-2">
                        <button 
                            wire:click="setManualStatus(true)"
                            wire:confirm="Buka koperasi?"
                            class="flex-1 py-2 px-4 text-sm font-medium rounded-lg {{ $currentStatus['is_open'] ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-green-600 text-white hover:bg-green-700' }}"
                            {{ $currentStatus['is_open'] ? 'disabled' : '' }}
                        >
                            Buka Koperasi
                        </button>
                        <button 
                            wire:click="setManualStatus(false)"
                            wire:confirm="Tutup koperasi?"
                            class="flex-1 py-2 px-4 text-sm font-medium rounded-lg {{ !$currentStatus['is_open'] ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-red-600 text-white hover:bg-red-700' }}"
                            {{ !$currentStatus['is_open'] ? 'disabled' : '' }}
                        >
                            Tutup Koperasi
                        </button>
                    </div>
                @endif
            </div>
        </x-layout.form-section>
    </x-ui.card>

    {{-- Poin SHU Settings Section --}}
    @can('kelola_pengaturan')
        <x-ui.card>
            <x-layout.form-section 
                title="Pengaturan Poin SHU"
                description="Konfigurasi nominal pembelian untuk mendapatkan 1 Poin SHU"
            >
                <form wire:submit.prevent="saveShuSettings" class="space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Nominal per 1 Poin (Rp)</label>
                            <input
                                type="text"
                                inputmode="numeric"
                                wire:model="shuConversionAmount"
                                placeholder="Contoh: 10000"
                                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl"
                            >
                            @error('shuConversionAmount') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="px-5 py-2.5 bg-primary-600 text-white font-semibold rounded-xl">
                            Simpan Pengaturan Poin SHU
                        </button>
                    </div>
                </form>
            </x-layout.form-section>
        </x-ui.card>
    @endcan

    {{-- Academic Holiday Section --}}
    <x-ui.card>
        <x-layout.form-section 
            title="Libur Akademik"
            description="Jadwal libur yang mempengaruhi keterangan buka"
        >
            <div class="space-y-4">
                {{-- Quick Custom Setting --}}
                @if($nextOpenMode === 'custom' || !empty($academicHolidayName))
                    <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-amber-800">{{ $academicHolidayName ?: 'Libur Kustom Aktif' }}</p>
                                @if($academicHolidayStart && $academicHolidayEnd)
                                    <p class="text-xs text-amber-600">{{ \Carbon\Carbon::parse($academicHolidayStart)->locale('id')->isoFormat('D MMM') }} - {{ \Carbon\Carbon::parse($academicHolidayEnd)->locale('id')->isoFormat('D MMM YYYY') }}</p>
                                @endif
                            </div>
                            <button wire:click="resetNextOpenMode" class="text-amber-600 hover:text-amber-800 text-sm">Reset</button>
                        </div>
                    </div>
                @endif

                {{-- Add/Edit Form Toggle --}}
                @if($showHolidayForm)
                    <div class="p-4 bg-gray-50 rounded-lg space-y-3">
                        <input type="text" wire:model="holidayForm.name" placeholder="Nama libur (cth: Libur Semester)" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <div class="grid grid-cols-2 gap-3">
                            <input type="date" wire:model="holidayForm.start_date" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <input type="date" wire:model="holidayForm.end_date" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="flex gap-2">
                            <button wire:click="saveHoliday" class="flex-1 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Simpan</button>
                            <button wire:click="cancelHolidayForm" class="px-4 py-2 text-sm text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Batal</button>
                        </div>
                    </div>
                @else
                    <button wire:click="openHolidayForm" class="w-full py-2 text-sm font-medium text-blue-600 border border-blue-200 rounded-lg hover:bg-blue-50">
                        + Tambah Libur Akademik
                    </button>
                @endif

                {{-- Holidays List --}}
                @if(count($academicHolidays) > 0)
                    <div class="divide-y divide-gray-100">
                        @foreach($academicHolidays as $holiday)
                            @php
                                $startDate = \Carbon\Carbon::parse($holiday['start_date']);
                                $endDate = \Carbon\Carbon::parse($holiday['end_date']);
                                $today = now()->startOfDay();
                                $isActive = $holiday['is_active'] && $startDate->lte($today) && $endDate->gte($today);
                                $isPast = $endDate->lt($today);
                            @endphp
                            <div class="py-3 flex items-center justify-between {{ $isPast ? 'opacity-50' : '' }}">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $holiday['name'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $startDate->locale('id')->isoFormat('D MMM') }} - {{ $endDate->locale('id')->isoFormat('D MMM YYYY') }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($isActive)
                                        <span class="px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-700">Aktif</span>
                                    @elseif(!$holiday['is_active'])
                                        <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-500">Nonaktif</span>
                                    @endif
                                    <button wire:click="editHoliday({{ $holiday['id'] }})" class="p-1 text-gray-400 hover:text-blue-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    </button>
                                    <button wire:click="deleteHoliday({{ $holiday['id'] }})" wire:confirm="Hapus libur ini?" class="p-1 text-gray-400 hover:text-red-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </x-layout.form-section>
    </x-ui.card>
</div>
