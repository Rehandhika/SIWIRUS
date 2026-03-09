<div class="max-w-2xl mx-auto">
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
                                    <img src="{{ $currentAttendance->check_in_photo_url }}" class="w-full h-32 object-contain rounded-lg border">
                                </div>
                            @endif
                        </div>
                    @else
                        <div>
                            @if($canCheckIn)
                                {{-- ========================================
                                     ALPINE-MANAGED UPLOAD COMPONENT 
                                     ======================================== --}}
                                <div
                                    x-data="photoUploader()"
                                    x-on:livewire-upload-start="handleUploadStart($event)"
                                    x-on:livewire-upload-finish="handleUploadFinish($event)"
                                    x-on:livewire-upload-error="handleUploadError($event)"
                                    x-on:livewire-upload-progress="handleUploadProgress($event)"
                                    class="mb-4"
                                >
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Foto Bukti Check-in <span class="text-red-500">*</span>
                                    </label>
                                    
                                    {{-- PREVIEW STATE: Photo selected & ready --}}
                                    <template x-if="previewUrl">
                                        <div class="relative mb-3">
                                            <img :src="previewUrl" class="w-full h-48 object-contain rounded-lg border-2 border-success-300">
                                            <button 
                                                type="button" 
                                                x-on:click="resetUpload()" 
                                                class="absolute top-2 right-2 bg-red-500 hover:bg-red-600 text-white rounded-full p-1 shadow-lg transition-colors"
                                            >
                                                <x-ui.icon name="x" class="w-4 h-4" />
                                            </button>

                                            {{-- Status overlay --}}
                                            <div 
                                                x-show="state === 'compressing' || state === 'uploading'"
                                                class="absolute inset-0 bg-black/40 rounded-lg flex flex-col items-center justify-center"
                                            >
                                                <x-ui.icon name="arrow-path" class="w-8 h-8 text-white animate-spin mb-2" />
                                                <span class="text-white text-sm font-medium" x-text="stateMessage"></span>
                                                {{-- Progress bar --}}
                                                <div x-show="state === 'uploading'" class="w-3/4 mt-2 bg-white/30 rounded-full h-2 overflow-hidden">
                                                    <div class="bg-white h-full rounded-full transition-all duration-300" :style="'width: ' + progress + '%'"></div>
                                                </div>
                                            </div>

                                            {{-- Success badge --}}
                                            <div 
                                                x-show="state === 'success'" 
                                                x-transition
                                                class="absolute top-2 left-2 bg-green-500 text-white rounded-full px-2 py-1 text-xs font-medium flex items-center gap-1 shadow"
                                            >
                                                <x-ui.icon name="check-circle" class="w-3.5 h-3.5" />
                                                Siap
                                            </div>
                                        </div>
                                    </template>

                                    {{-- IDLE STATE: No photo selected --}}
                                    <template x-if="!previewUrl">
                                        <div class="flex items-center justify-center w-full">
                                            <label class="flex flex-col items-center justify-center w-full h-48 border-2 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition-colors"
                                                :class="state === 'error' ? 'border-red-400 bg-red-50' : 'border-gray-300'"
                                            >
                                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                                    <x-ui.icon name="camera" class="w-10 h-10 mb-3 text-gray-400" />
                                                    <p class="mb-2 text-sm text-gray-500"><span class="font-semibold">Klik untuk ambil foto</span></p>
                                                    <p class="text-xs text-gray-400">Maks. 10MB • JPG, PNG, HEIC</p>
                                                </div>
                                                <input 
                                                    type="file" 
                                                    class="hidden" 
                                                    accept="image/*"
                                                    x-ref="fileInput"
                                                    x-on:change="handleFileSelect($event)"
                                                >
                                            </label>
                                        </div>
                                    </template>

                                    {{-- Error message from upload --}}
                                    <template x-if="errorMessage">
                                        <p class="mt-2 text-sm text-red-600 flex items-center gap-1">
                                            <x-ui.icon name="exclamation-triangle" class="w-4 h-4 flex-shrink-0" />
                                            <span x-text="errorMessage"></span>
                                        </p>
                                    </template>

                                    {{-- Livewire validation error --}}
                                    @error('checkInPhoto') 
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
                                    @enderror
                                </div>

                                {{-- CHECK-IN BUTTON --}}
                                <x-ui.button 
                                    variant="success" 
                                    wire:click="checkIn" 
                                    x-bind:disabled="state !== 'success'"
                                    class="w-full"
                                >
                                    <x-ui.icon name="check-circle" class="w-5 h-5 mr-2" />
                                    <span wire:loading.remove wire:target="checkIn">Check-in</span>
                                    <span wire:loading wire:target="checkIn">
                                        <x-ui.icon name="arrow-path" class="w-4 h-4 inline animate-spin mr-2" />
                                        Memproses...
                                    </span>
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

@script
<script>
    Alpine.data('photoUploader', () => ({
        // States: idle, compressing, uploading, success, error
        state: 'idle',
        previewUrl: null,
        errorMessage: null,
        progress: 0,

        // Max file size before compression (2MB)
        COMPRESS_THRESHOLD: 2 * 1024 * 1024,
        // Max allowed file size from input (10MB original)
        MAX_FILE_SIZE: 10 * 1024 * 1024,
        // Target max dimension for resize
        MAX_DIMENSION: 1920,
        // JPEG quality for compression
        JPEG_QUALITY: 0.8,

        get stateMessage() {
            switch (this.state) {
                case 'compressing': return 'Mengompres foto...';
                case 'uploading': return `Mengunggah... ${this.progress}%`;
                default: return '';
            }
        },

        async handleFileSelect(event) {
            const file = event.target.files[0];
            if (!file) return;

            // Reset previous state
            this.errorMessage = null;
            this.progress = 0;

            // 1. Validate basic type
            if (!file.type.startsWith('image/') && !file.name.match(/\.(heic|heif)$/i)) {
                this.showError('File harus berupa gambar (JPG, PNG, HEIC).');
                this.clearInput();
                return;
            }

            // 2. Validate max size (before compression)
            if (file.size > this.MAX_FILE_SIZE) {
                const sizeMB = (file.size / 1024 / 1024).toFixed(1);
                this.showError(`Ukuran foto terlalu besar (${sizeMB}MB). Maksimal 10MB.`);
                this.clearInput();
                return;
            }

            // 3. Generate preview immediately from original file
            this.generatePreview(file);

            // 4. Compress if needed, then upload
            try {
                let fileToUpload = file;

                if (file.size > this.COMPRESS_THRESHOLD) {
                    this.state = 'compressing';
                    fileToUpload = await this.compressImage(file);
                }

                // 5. Upload to Livewire temp storage
                this.state = 'uploading';
                await this.uploadToLivewire(fileToUpload);
            } catch (err) {
                console.error('Photo upload error:', err);
                this.showError('Gagal memproses foto. Silakan coba lagi.');
                this.previewUrl = null;
            }
        },

        generatePreview(file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                this.previewUrl = e.target.result;
            };
            reader.readAsDataURL(file);
        },

        compressImage(file) {
            return new Promise((resolve, reject) => {
                const img = new Image();
                const url = URL.createObjectURL(file);

                img.onload = () => {
                    try {
                        let { width, height } = img;

                        // Calculate new dimensions
                        if (width > this.MAX_DIMENSION || height > this.MAX_DIMENSION) {
                            const ratio = Math.min(this.MAX_DIMENSION / width, this.MAX_DIMENSION / height);
                            width = Math.round(width * ratio);
                            height = Math.round(height * ratio);
                        }

                        const canvas = document.createElement('canvas');
                        canvas.width = width;
                        canvas.height = height;

                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, width, height);

                        canvas.toBlob(
                            (blob) => {
                                URL.revokeObjectURL(url);
                                if (!blob) {
                                    reject(new Error('Kompresi gagal'));
                                    return;
                                }
                                // Create a new File from the blob
                                const compressed = new File(
                                    [blob], 
                                    file.name.replace(/\.\w+$/, '.jpg'), 
                                    { type: 'image/jpeg', lastModified: Date.now() }
                                );
                                resolve(compressed);
                            },
                            'image/jpeg',
                            this.JPEG_QUALITY
                        );
                    } catch (e) {
                        URL.revokeObjectURL(url);
                        reject(e);
                    }
                };

                img.onerror = () => {
                    URL.revokeObjectURL(url);
                    // If image can't be loaded (e.g. HEIC on some browsers), upload original
                    resolve(file);
                };

                img.src = url;
            });
        },

        uploadToLivewire(file) {
            // Use Livewire's JavaScript upload API for granular control
            return new Promise((resolve, reject) => {
                @this.upload('checkInPhoto', file,
                    // Success callback
                    () => {
                        this.state = 'success';
                        this.progress = 100;
                        // Tell the server the photo is ready
                        @this.call('markPhotoReady');
                        resolve();
                    },
                    // Error callback
                    (error) => {
                        this.showError('Gagal mengunggah foto. Periksa koneksi internet Anda.');
                        this.previewUrl = null;
                        reject(error);
                    },
                    // Progress callback
                    (event) => {
                        this.progress = event.detail?.progress || Math.round((event.loaded / event.total) * 100) || 0;
                    }
                );
            });
        },

        // Livewire global upload event handlers (fallback/redundancy)
        handleUploadStart(event) {
            // Already managed by uploadToLivewire, but just in case
            if (this.state !== 'uploading' && this.state !== 'compressing') {
                this.state = 'uploading';
            }
        },
        handleUploadFinish(event) {
            if (this.state === 'uploading') {
                this.state = 'success';
                this.progress = 100;
            }
        },
        handleUploadError(event) {
            if (this.state === 'uploading') {
                this.showError('Gagal mengunggah foto. Silakan coba lagi.');
                this.previewUrl = null;
            }
        },
        handleUploadProgress(event) {
            if (this.state === 'uploading' && event.detail?.progress) {
                this.progress = event.detail.progress;
            }
        },

        resetUpload() {
            this.previewUrl = null;
            this.state = 'idle';
            this.errorMessage = null;
            this.progress = 0;
            this.clearInput();
            @this.call('removePhoto');
        },

        showError(msg) {
            this.state = 'error';
            this.errorMessage = msg;
        },

        clearInput() {
            if (this.$refs.fileInput) {
                this.$refs.fileInput.value = '';
            }
        }
    }));
</script>
@endscript
