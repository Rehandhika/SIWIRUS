<div class="max-w-2xl mx-auto" 
     x-data="{ 
        libStatus: 'loading',
        init() {
            if (typeof heic2any !== 'undefined') {
                this.libStatus = 'ready';
                return;
            }
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/heic2any@0.0.4/dist/heic2any.min.js';
            script.onload = () => { this.libStatus = 'ready'; console.log('HEIC Library Loaded'); };
            script.onerror = () => { this.libStatus = 'error'; console.error('HEIC Library Failed to Load'); };
            document.head.appendChild(script);
        }
     }"
>
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
                                        Jadwal: {{ $currentSchedule->day_label }}, {{ $currentSchedule->date->format('d M Y') }}
                                    </h3>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $status['badge'] }}">
                                        {{ $status['label'] }}
                                    </span>
                                </div>
                                <div class="mt-2 text-sm {{ $status['text'] }}">
                                    <p>Sesi {{ $currentSchedule->session }}: {{ $currentSchedule->session_label }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Status Message for Upcoming --}}
            @if($scheduleStatus === 'upcoming')
                <div class="mb-6">
                    <x-ui.alert variant="info">
                        <div class="flex items-center">
                            <x-ui.icon name="info-circle" class="h-5 w-5 mr-2" />
                            <span>Check-in tersedia {{ $timeUntilCheckIn }}.</span>
                        </div>
                    </x-ui.alert>
                </div>
            @endif
        @elseif($isOverrideActive)
            <div class="mb-6">
                <x-ui.alert variant="info">
                    <div class="flex items-center">
                        <x-ui.icon name="info-circle" class="h-5 w-5 mr-2" />
                        <span>Mode check-in bebas aktif.</span>
                    </div>
                </x-ui.alert>
            </div>
        @endif

        @if($currentSchedule || $isOverrideActive)
            {{-- Check-in/Check-out Section --}}
            <x-layout.grid cols="2" gap="6" class="mb-6">
                {{-- Check-in Card --}}
                <x-ui.card padding="true" shadow="sm">
                    <h4 class="text-sm font-medium text-gray-900 mb-3">Check-in</h4>
                    @if($checkInTime)
                        <div class="text-center">
                            <div class="text-2xl font-bold text-success-600 mb-2">{{ $checkInTime }}</div>
                            <p class="text-sm text-gray-500 mb-3">Sudah check-in</p>
                            @if($currentAttendance && $currentAttendance->check_in_photo)
                                <div class="mt-3">
                                    <img src="{{ $currentAttendance->check_in_photo_url }}" class="w-full h-32 object-cover rounded-lg border">
                                </div>
                            @endif
                        </div>
                    @else
                        <div
                            x-data="{
                                uploading: false,
                                async compressAndUpload(event) {
                                    let file = event.target.files[0];
                                    if (!file) return;
                                    
                                    this.uploading = true;
                                    
                                    try {
                                        const isHeic = file.name.toLowerCase().endsWith('.heic') || file.name.toLowerCase().endsWith('.heif') || file.type === 'image/heic' || file.type === 'image/heif';

                                        if (isHeic) {
                                            if (typeof heic2any === 'undefined') {
                                                this.uploading = false;
                                                alert('Modul iPhone sedang dimuat. Harap tunggu 3 detik lalu coba pilih foto kembali.');
                                                return;
                                            }
                                            console.log('Converting HEIC...');
                                            const converted = await heic2any({
                                                blob: file,
                                                toType: 'image/jpeg',
                                                quality: 0.7
                                            });
                                            const blob = Array.isArray(converted) ? converted[0] : converted;
                                            file = new File([blob], 'from_iphone.jpg', { type: 'image/jpeg' });
                                        }

                                        // Use ObjectURL for better performance
                                        const url = URL.createObjectURL(file);
                                        const img = new Image();
                                        img.src = url;
                                        
                                        img.onerror = () => {
                                            URL.revokeObjectURL(url);
                                            this.uploading = false;
                                            alert('Format gambar tidak dikenali. Jika Anda menggunakan iPhone, pastikan koneksi internet stabil.');
                                        };

                                        img.onload = () => {
                                            const canvas = document.createElement('canvas');
                                            const MAX_WIDTH = 1000;
                                            const MAX_HEIGHT = 1000;
                                            let width = img.width;
                                            let height = img.height;

                                            if (width > height) {
                                                if (width > MAX_WIDTH) { height *= MAX_WIDTH / width; width = MAX_WIDTH; }
                                            } else {
                                                if (height > MAX_HEIGHT) { width *= MAX_HEIGHT / height; height = MAX_HEIGHT; }
                                            }
                                            
                                            canvas.width = width;
                                            canvas.height = height;
                                            const ctx = canvas.getContext('2d');
                                            ctx.drawImage(img, 0, 0, width, height);
                                            
                                            canvas.toBlob((blob) => {
                                                URL.revokeObjectURL(url);
                                                const finalFile = new File([blob], 'attendance.jpg', { type: 'image/jpeg' });
                                                @this.upload('checkInPhoto', finalFile, 
                                                    () => this.uploading = false, 
                                                    () => { this.uploading = false; alert('Gagal mengunggah foto ke server.'); }
                                                );
                                            }, 'image/jpeg', 0.8);
                                        };
                                    } catch (err) {
                                        console.error('Processing error:', err);
                                        this.uploading = false;
                                        alert('Gagal memproses gambar: ' + err.message);
                                    }
                                }
                            }"
                        >
                            @if($canCheckIn)
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Foto Bukti Check-in <span class="text-red-500">*</span></label>
                                    
                                    @if($checkInPhotoPreview)
                                        <div class="relative mb-3">
                                            <img src="{{ $checkInPhotoPreview }}" class="w-full h-48 object-cover rounded-lg border-2 border-success-300">
                                            <button type="button" wire:click="removePhoto" class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1"><x-ui.icon name="x" class="w-4 h-4" /></button>
                                        </div>
                                    @else
                                        <div class="flex items-center justify-center w-full" x-show="!uploading">
                                            <label class="flex flex-col items-center justify-center w-full h-48 border-2 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100">
                                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                                    <x-ui.icon name="camera" class="w-10 h-10 mb-3 text-gray-400" />
                                                    <p class="mb-2 text-sm text-gray-500"><span class="font-semibold">Klik untuk ambil foto</span></p>
                                                    <p class="text-xs text-gray-500 text-center px-2">Mendukung iPhone (HEIC otomatis dikonversi)</p>
                                                </div>
                                                <input type="file" class="hidden" accept="image/*" @change="compressAndUpload">
                                            </label>
                                        </div>
                                        <div class="flex flex-col items-center justify-center h-48 border-2 border-dashed rounded-lg bg-gray-50" x-show="uploading">
                                            <x-ui.icon name="arrow-path" class="w-8 h-8 animate-spin text-primary-500 mb-2" />
                                            <p class="text-sm text-gray-500 font-medium">Memproses foto iPhone...</p>
                                            <p class="text-xs text-gray-400 mt-1">Langkah ini memakan waktu beberapa detik</p>
                                        </div>
                                    @endif
                                    @error('checkInPhoto') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <x-ui.button variant="success" wire:click="checkIn" :disabled="!$checkInPhoto" class="w-full">
                                    <x-ui.icon name="check-circle" class="w-5 h-5 mr-2" />
                                    <span>Check-in</span>
                                </x-ui.button>
                            @else
                                <div class="text-center text-gray-500">
                                    <x-ui.icon name="clock" class="w-12 h-12 mx-auto mb-3 text-gray-400" />
                                    <p class="text-sm font-medium">Belum waktunya check-in</p>
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
                        </div>
                    @elseif($checkInTime)
                        <div class="text-center">
                            <x-ui.button variant="info" wire:click="checkOut" class="w-full">
                                <x-ui.icon name="logout" class="h-5 w-5 mr-2" />
                                <span>Check-out</span>
                            </x-ui.button>
                        </div>
                    @else
                        <div class="text-center text-gray-500">
                            <x-ui.icon name="lock-closed" class="w-12 h-12 mx-auto mb-3 text-gray-400" />
                            <p class="text-sm">Check-in dahulu</p>
                        </div>
                    @endif
                </x-ui.card>
            </x-layout.grid>
        @else
            <x-layout.empty-state icon="calendar" title="Tidak ada jadwal" description="Tidak ada jadwal kerja untuk Anda saat ini." />
        @endif
    </x-ui.card>
</div>
