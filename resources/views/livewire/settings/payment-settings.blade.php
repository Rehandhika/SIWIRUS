<div class="space-y-6">
    <x-layout.page-header 
        title="Pengaturan Pembayaran"
        description="Kelola metode pembayaran yang tersedia di sistem POS"
    />

    {{-- General Error Alert --}}
    @error('general')
        <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <p class="text-sm font-medium text-red-800">{{ $message }}</p>
            </div>
        </div>
    @enderror

    <x-ui.card>
        <form wire:submit="save" class="space-y-6">
            {{-- Payment Methods Section --}}
            <x-layout.form-section 
                title="Metode Pembayaran"
                description="Aktifkan atau nonaktifkan metode pembayaran yang tersedia"
            >
                <div class="space-y-4">
                    {{-- Info Banner --}}
                    <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <p class="text-sm text-blue-700">Minimal satu metode pembayaran harus aktif.</p>
                        </div>
                    </div>

                    {{-- Cash Payment Toggle --}}
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full {{ $cashEnabled ? 'bg-green-100' : 'bg-gray-200' }} flex items-center justify-center">
                                <svg class="w-5 h-5 {{ $cashEnabled ? 'text-green-600' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium {{ $cashEnabled ? 'text-green-700' : 'text-gray-700' }}">Tunai (Cash)</p>
                                <p class="text-sm text-gray-500">Pembayaran langsung dengan uang tunai</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model.live="cashEnabled" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                        </label>
                    </div>

                    {{-- Transfer Payment Toggle --}}
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full {{ $transferEnabled ? 'bg-blue-100' : 'bg-gray-200' }} flex items-center justify-center">
                                <svg class="w-5 h-5 {{ $transferEnabled ? 'text-blue-600' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium {{ $transferEnabled ? 'text-blue-700' : 'text-gray-700' }}">Transfer Bank</p>
                                <p class="text-sm text-gray-500">Pembayaran melalui transfer rekening bank</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model.live="transferEnabled" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>

                    {{-- QRIS Payment Toggle --}}
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full {{ $qrisEnabled ? 'bg-blue-100' : 'bg-gray-200' }} flex items-center justify-center">
                                <svg class="w-5 h-5 {{ $qrisEnabled ? 'text-blue-600' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium {{ $qrisEnabled ? 'text-blue-700' : 'text-gray-700' }}">QRIS</p>
                                <p class="text-sm text-gray-500">Pembayaran digital via scan QR code</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model.live="qrisEnabled" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                </div>
            </x-layout.form-section>

            {{-- QRIS Configuration Section --}}
            @if($qrisEnabled)
            <x-layout.form-section 
                title="Konfigurasi QRIS"
                description="Upload gambar QR code untuk pembayaran QRIS"
            >
                <div class="space-y-4">
                    {{-- QRIS Image Upload --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Gambar QRIS <span class="text-red-500">*</span>
                        </label>
                        
                        @if($this->qrisImageUrl)
                            {{-- Preview existing/uploaded image --}}
                            <div class="relative group w-full max-w-xs">
                                <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden border-2 border-blue-200">
                                    <img 
                                        src="{{ $this->qrisImageUrl }}" 
                                        alt="QRIS Preview" 
                                        class="w-full h-full object-contain p-2"
                                    >
                                </div>
                                
                                {{-- Overlay Actions --}}
                                <div class="absolute inset-0 max-w-xs aspect-square bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center gap-2">
                                    {{-- Change Image --}}
                                    <label class="cursor-pointer p-2 bg-white rounded-full hover:bg-gray-100 transition">
                                        <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <input 
                                            type="file" 
                                            wire:model="qrisImage"
                                            accept="image/jpeg,image/png,image/jpg"
                                            class="hidden"
                                        >
                                    </label>
                                    
                                    {{-- Remove Image --}}
                                    <button 
                                        type="button"
                                        wire:click="removeQrisImage"
                                        wire:confirm="Hapus gambar QRIS ini?"
                                        class="p-2 bg-white rounded-full hover:bg-gray-100 transition"
                                    >
                                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>

                                {{-- Loading Overlay --}}
                                <div 
                                    wire:loading 
                                    wire:target="qrisImage"
                                    class="absolute inset-0 max-w-xs aspect-square bg-white/80 rounded-lg flex items-center justify-center"
                                >
                                    <div class="text-center">
                                        <svg class="animate-spin h-8 w-8 text-blue-600 mx-auto" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <p class="mt-2 text-sm text-gray-600">Mengupload...</p>
                                    </div>
                                </div>
                            </div>
                        @else
                            {{-- Empty Upload Area --}}
                            <label 
                                class="flex flex-col items-center justify-center w-full max-w-xs aspect-square border-2 border-dashed rounded-lg cursor-pointer transition-colors border-gray-300 hover:border-blue-400 bg-gray-50 hover:bg-blue-50"
                                x-data="{ isDragging: false }"
                                x-on:dragover.prevent="isDragging = true"
                                x-on:dragleave.prevent="isDragging = false"
                                x-on:drop.prevent="isDragging = false"
                                :class="isDragging ? 'border-blue-500 bg-blue-50' : ''"
                            >
                                <div class="flex flex-col items-center justify-center p-6 text-center" wire:loading.remove wire:target="qrisImage">
                                    <svg class="w-10 h-10 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                    </svg>
                                    <p class="mb-1 text-sm text-gray-600">
                                        <span class="font-semibold text-blue-600">Klik untuk upload</span> atau drag & drop
                                    </p>
                                    <p class="text-xs text-gray-500">JPG, JPEG, PNG (Maks. 2MB)</p>
                                </div>

                                {{-- Loading State --}}
                                <div wire:loading wire:target="qrisImage" class="flex flex-col items-center justify-center p-6">
                                    <svg class="animate-spin h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-600">Mengupload...</p>
                                </div>

                                <input 
                                    type="file" 
                                    wire:model="qrisImage"
                                    accept="image/jpeg,image/png,image/jpg"
                                    class="hidden"
                                >
                            </label>
                        @endif

                        @error('qrisImage')
                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror

                        <p class="mt-2 text-xs text-gray-500">Upload gambar QR code QRIS yang akan ditampilkan kepada pelanggan saat pembayaran.</p>
                    </div>
                </div>
            </x-layout.form-section>
            @endif

            {{-- Transfer Bank Configuration Section --}}
            @if($transferEnabled)
            <x-layout.form-section 
                title="Konfigurasi Transfer Bank"
                description="Kelola rekening bank untuk pembayaran transfer. Anda dapat menambahkan beberapa rekening."
            >
                <div class="space-y-4">
                    {{-- Bank Accounts List --}}
                    @if(count($bankAccounts) > 0)
                        <div class="space-y-3">
                            @foreach($bankAccounts as $bank)
                                <div class="flex items-center justify-between p-4 bg-white rounded-lg border {{ ($bank['is_active'] ?? true) ? 'border-blue-200' : 'border-gray-200 opacity-60' }}">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full {{ ($bank['is_active'] ?? true) ? 'bg-blue-100' : 'bg-gray-100' }} flex items-center justify-center">
                                            <svg class="w-5 h-5 {{ ($bank['is_active'] ?? true) ? 'text-blue-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900">{{ $bank['bank_name'] }}</p>
                                            <p class="text-sm text-gray-500">
                                                <span class="font-mono">{{ $bank['account_number'] }}</span>
                                                <span class="mx-1">•</span>
                                                {{ $bank['account_holder'] }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        {{-- Toggle Active --}}
                                        <button 
                                            type="button"
                                            wire:click="toggleBank('{{ $bank['id'] }}')"
                                            class="p-2 rounded-lg hover:bg-gray-100 transition"
                                            title="{{ ($bank['is_active'] ?? true) ? 'Nonaktifkan' : 'Aktifkan' }}"
                                        >
                                            @if($bank['is_active'] ?? true)
                                                <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                </svg>
                                            @else
                                                <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                </svg>
                                            @endif
                                        </button>
                                        
                                        {{-- Edit --}}
                                        <button 
                                            type="button"
                                            wire:click="editBank('{{ $bank['id'] }}')"
                                            class="p-2 rounded-lg hover:bg-gray-100 transition"
                                            title="Edit"
                                        >
                                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        
                                        {{-- Delete --}}
                                        <button 
                                            type="button"
                                            wire:click="deleteBank('{{ $bank['id'] }}')"
                                            wire:confirm="Hapus rekening bank ini?"
                                            class="p-2 rounded-lg hover:bg-red-50 transition"
                                            title="Hapus"
                                        >
                                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-6 bg-gray-50 border border-dashed border-gray-300 rounded-lg text-center">
                            <svg class="w-12 h-12 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                            <p class="text-gray-600 mb-1">Belum ada rekening bank</p>
                            <p class="text-sm text-gray-500">Tambahkan rekening bank untuk menerima pembayaran transfer</p>
                        </div>
                    @endif

                    {{-- Add Bank Button --}}
                    @if(!$showBankForm)
                        <button 
                            type="button"
                            wire:click="openAddBankForm"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Tambah Rekening Bank
                        </button>
                    @endif

                    {{-- Bank Form (Add/Edit) --}}
                    @if($showBankForm)
                        <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg space-y-4">
                            <div class="flex items-center justify-between">
                                <h4 class="font-medium text-blue-800">
                                    {{ $editingBankId ? 'Edit Rekening Bank' : 'Tambah Rekening Bank Baru' }}
                                </h4>
                                <button 
                                    type="button"
                                    wire:click="closeBankForm"
                                    class="p-1 rounded hover:bg-blue-100 transition"
                                >
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>

                            {{-- Bank Name --}}
                            <div>
                                <label for="bankName" class="block text-sm font-medium text-gray-700 mb-1">
                                    Nama Bank <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    id="bankName"
                                    wire:model="bankName" 
                                    placeholder="Contoh: BCA, BNI, Mandiri"
                                    class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('bankName') border-red-300 @else border-gray-300 @enderror"
                                >
                                @error('bankName')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Account Number --}}
                            <div>
                                <label for="accountNumber" class="block text-sm font-medium text-gray-700 mb-1">
                                    Nomor Rekening <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    id="accountNumber"
                                    wire:model="accountNumber" 
                                    placeholder="Contoh: 1234567890"
                                    class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('accountNumber') border-red-300 @else border-gray-300 @enderror"
                                >
                                @error('accountNumber')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Account Holder Name --}}
                            <div>
                                <label for="accountHolder" class="block text-sm font-medium text-gray-700 mb-1">
                                    Nama Pemilik Rekening <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    id="accountHolder"
                                    wire:model="accountHolder" 
                                    placeholder="Contoh: PT Koperasi Mahasiswa"
                                    class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('accountHolder') border-red-300 @else border-gray-300 @enderror"
                                >
                                @error('accountHolder')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Form Actions --}}
                            <div class="flex justify-end gap-2 pt-2">
                                <button 
                                    type="button"
                                    wire:click="closeBankForm"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition"
                                >
                                    Batal
                                </button>
                                <button 
                                    type="button"
                                    wire:click="saveBank"
                                    wire:loading.attr="disabled"
                                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition"
                                >
                                    <span wire:loading.remove wire:target="saveBank">
                                        {{ $editingBankId ? 'Simpan Perubahan' : 'Tambah Rekening' }}
                                    </span>
                                    <span wire:loading wire:target="saveBank">
                                        <svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Menyimpan...
                                    </span>
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </x-layout.form-section>
            @endif

            {{-- Actions --}}
            <div class="flex justify-end pt-4 border-t border-gray-200">
                <button 
                    type="submit" 
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50 cursor-not-allowed"
                    class="inline-flex items-center px-6 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors"
                >
                    <span wire:loading.remove wire:target="save">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </span>
                    <span wire:loading wire:target="save">
                        <svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                    Simpan Pengaturan
                </button>
            </div>
        </form>
    </x-ui.card>
</div>
