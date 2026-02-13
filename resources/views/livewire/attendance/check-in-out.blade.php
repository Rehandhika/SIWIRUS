<div class="max-w-2xl mx-auto" wire:poll.60s="refreshSchedule">
    <x-ui.card title="Absensi Hari Ini">
        @if($currentSchedule)
            {{-- Schedule Info with Status --}}
            <div class="mb-6">
                @php
                    $statusConfig = [
                        'active' => [
                            'bg' => 'bg-success-50',
                            'border' => 'border-success-500',
                            'text' => 'text-success-800',
                            'icon' => 'text-success-400',
                            'badge' => 'bg-success-100 text-success-800',
                            'label' => 'Sedang Berlangsung'
                        ],
                        'upcoming' => [
                            'bg' => 'bg-info-50',
                            'border' => 'border-info-500',
                            'text' => 'text-info-800',
                            'icon' => 'text-info-400',
                            'badge' => 'bg-info-100 text-info-800',
                            'label' => 'Akan Datang'
                        ],
                        'past' => [
                            'bg' => 'bg-warning-50',
                            'border' => 'border-warning-500',
                            'text' => 'text-warning-800',
                            'icon' => 'text-warning-400',
                            'badge' => 'bg-warning-100 text-warning-800',
                            'label' => 'Sudah Lewat'
                        ]
                    ];
                    $status = $statusConfig[$scheduleStatus] ?? $statusConfig['active'];
                @endphp
                
                <div class="{{ $status['bg'] }} border-l-4 {{ $status['border'] }} p-4 rounded-lg">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start flex-1">
                            <div class="flex-shrink-0">
                                <x-ui.icon name="clock" class="h-5 w-5 {{ $status['icon'] }}" />
                            </div>
                            <div class="ml-3 flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <h3 class="text-sm font-medium {{ $status['text'] }}">
                                        Jadwal Hari Ini: {{ $currentSchedule->day_label }}, {{ $currentSchedule->date->format('d M Y') }}
                                    </h3>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $status['badge'] }}">
                                        {{ $status['label'] }}
                                    </span>
                                </div>
                                <div class="mt-2 text-sm {{ $status['text'] }} space-y-1">
                                    <p>Sesi {{ $currentSchedule->session }}: {{ $currentSchedule->session_label }}</p>
                                    <p>Waktu: {{ \Carbon\Carbon::parse($currentSchedule->time_start)->format('H:i') }} - {{ \Carbon\Carbon::parse($currentSchedule->time_end)->format('H:i') }}</p>
                                    @if($currentSchedule->schedule)
                                        <p class="text-xs opacity-75">Jadwal: {{ $currentSchedule->schedule->week_start_date->format('d M') }} - {{ $currentSchedule->schedule->week_end_date->format('d M Y') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Status Message for Upcoming/Past Schedule --}}
            @if($scheduleStatus === 'upcoming')
                <div class="mb-6">
                    <x-ui.alert variant="info">
                        <div class="flex items-center">
                            <x-ui.icon name="info-circle" class="h-5 w-5 mr-2" />
                            <span>Check-in dapat dilakukan 30 menit sebelum jadwal dimulai. @if($timeUntilCheckIn) Check-in tersedia {{ $timeUntilCheckIn }}. @endif</span>
                        </div>
                    </x-ui.alert>
                </div>
            @elseif($scheduleStatus === 'past' && !$checkInTime)
                <div class="mb-6">
                    <x-ui.alert variant="warning">
                        <div class="flex items-center">
                            <x-ui.icon name="exclamation-triangle" class="h-5 w-5 mr-2" />
                            <span>Jadwal sudah lewat. Anda masih dapat check-in untuk mencatat keterlambatan.</span>
                        </div>
                    </x-ui.alert>
                </div>
            @endif
        @elseif(config('siwirus.attendance.override_mode', false))
            <div class="mb-6">
                <x-ui.alert variant="info">
                    <div class="flex items-center">
                        <x-ui.icon name="information-circle" class="h-5 w-5 mr-2" />
                        <span>Mode check-in bebas aktif. Anda dapat check-in tanpa jadwal.</span>
                    </div>
                </x-ui.alert>
            </div>
        @endif

        @if($currentSchedule || config('siwirus.attendance.override_mode', false))
            {{-- Check-in/Check-out Section --}}
            <x-layout.grid cols="2" gap="6" class="mb-6">
                {{-- Check-in Card --}}
                <x-ui.card padding="true" shadow="sm">
                    <h4 class="text-sm font-medium text-gray-900 mb-3">Check-in</h4>
                    @if($checkInTime)
                        <div class="text-center">
                            <div class="text-2xl font-bold text-success-600 mb-2">{{ $checkInTime }}</div>
                            <p class="text-sm text-gray-500 mb-3">Sudah check-in</p>
                            
                            {{-- Show check-in photo if exists --}}
                            @if($currentAttendance && $currentAttendance->check_in_photo)
                                <div class="mt-3">
                                    <img src="{{ $currentAttendance->check_in_photo_url }}" 
                                         alt="Foto Check-in" 
                                         class="w-full h-32 object-cover rounded-lg border border-gray-200 cursor-pointer hover:opacity-90 transition"
                                         onclick="window.open('{{ $currentAttendance->check_in_photo_url }}', '_blank')">
                                    <p class="text-xs text-gray-500 mt-1">Klik untuk memperbesar</p>
                                </div>
                            @endif
                        </div>
                    @else
                        <div>
                            @if($canCheckIn)
                                {{-- Photo Upload Section --}}
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Foto Bukti Check-in <span class="text-red-500">*</span>
                                    </label>
                                    
                                    @if($showPhotoPreview && $checkInPhotoPreview)
                                        <div class="relative">
                                            <img src="{{ $checkInPhotoPreview }}" 
                                                 alt="Preview" 
                                                 class="w-full h-48 object-cover rounded-lg border-2 border-success-300">
                                            <button type="button" 
                                                    wire:click="removePhoto"
                                                    class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 transition">
                                                <x-ui.icon name="x" class="w-4 h-4" />
                                            </button>
                                        </div>
                                        <div wire:loading wire:target="checkInPhoto" class="mt-2 text-sm text-gray-500">
                                            <x-ui.icon name="arrow-path" class="w-4 h-4 inline animate-spin" />
                                            Mengunggah foto...
                                        </div>
                                    @else
                                        <div class="flex items-center justify-center w-full">
                                            <label for="checkInPhoto" class="flex flex-col items-center justify-center w-full h-48 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition">
                                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                                    <x-ui.icon name="camera" class="w-10 h-10 mb-3 text-gray-400" />
                                                    <p class="mb-2 text-sm text-gray-500">
                                                        <span class="font-semibold">Klik untuk upload</span>
                                                    </p>
                                                    <p class="text-xs text-gray-500">PNG, JPG (Max. 5MB)</p>
                                                </div>
                                                <input id="checkInPhoto" 
                                                       type="file" 
                                                       class="hidden" 
                                                       wire:model="checkInPhoto"
                                                       accept="image/*">
                                            </label>
                                        </div>
                                    @endif
                                    
                                    @error('checkInPhoto')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                    
                                    <div wire:loading wire:target="checkInPhoto" class="mt-2 text-sm text-gray-500">
                                        <x-ui.icon name="arrow-path" class="w-4 h-4 inline animate-spin" />
                                        Mengunggah foto...
                                    </div>
                                </div>

                                {{-- Check-in Button --}}
                                <x-ui.button 
                                    variant="success" 
                                    wire:click="checkIn"
                                    :disabled="!$checkInPhoto"
                                    class="w-full"
                                >
                                    <x-ui.icon name="check-circle" class="w-5 h-5 mr-2" />
                                    <span wire:loading.remove wire:target="checkIn">Check-in</span>
                                    <span wire:loading wire:target="checkIn">
                                        <x-ui.icon name="arrow-path" class="w-5 h-5 inline animate-spin mr-2" />
                                        Memproses...
                                    </span>
                                </x-ui.button>
                            @else
                                <div class="text-center text-gray-500">
                                    <x-ui.icon name="clock" class="w-12 h-12 mx-auto mb-3 text-gray-400" />
                                    <p class="text-sm font-medium">Belum waktunya check-in</p>
                                    @if($timeUntilCheckIn)
                                        <p class="text-xs mt-1">Tersedia {{ $timeUntilCheckIn }}</p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif
                </x-ui.card>

                {{-- Check-out Card --}}
                <x-ui.card padding="true" shadow="sm">
                    <h4 class="text-sm font-medium text-gray-900 mb-3">Check-out</h4>
                    @if($checkOutTime)
                        <div class="text-center">
                            <div class="text-2xl font-bold text-info-600">{{ $checkOutTime }}</div>
                            <p class="text-sm text-gray-500">Sudah check-out</p>
                            @if($currentAttendance && $currentAttendance->work_hours)
                                <div class="mt-3 p-2 bg-info-50 rounded-lg">
                                    <p class="text-xs text-info-700">Total Jam Kerja</p>
                                    <p class="text-lg font-semibold text-info-900">{{ number_format($currentAttendance->work_hours, 2) }} jam</p>
                                </div>
                            @endif
                        </div>
                    @elseif($checkInTime)
                        <div class="text-center">
                            <x-ui.button 
                                variant="info" 
                                wire:click="checkOut"
                                class="w-full"
                            >
                                <x-ui.icon name="logout" class="w-5 h-5 mr-2" />
                                <span wire:loading.remove wire:target="checkOut">Check-out</span>
                                <span wire:loading wire:target="checkOut">
                                    <x-ui.icon name="arrow-path" class="w-5 h-5 inline animate-spin mr-2" />
                                    Memproses...
                                </span>
                            </x-ui.button>
                            <p class="text-xs text-gray-500 mt-2">Tidak perlu foto untuk check-out</p>
                        </div>
                    @else
                        <div class="text-center text-gray-500">
                            <x-ui.icon name="lock-closed" class="w-12 h-12 mx-auto mb-3 text-gray-400" />
                            <p class="text-sm">Check-in terlebih dahulu</p>
                        </div>
                    @endif
                </x-ui.card>
            </x-layout.grid>

        @else
            <x-layout.empty-state
                icon="calendar"
                title="Tidak ada jadwal aktif"
                description="Saat ini tidak ada jadwal kerja yang aktif untuk Anda."
            />
        @endif
    </x-ui.card>

    {{-- Flash Messages --}}
    

    
</div>
