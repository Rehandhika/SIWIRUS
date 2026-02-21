<div class="max-w-4xl mx-auto space-y-6">
    <x-layout.page-header 
        title="Edit Profil"
        description="Kelola informasi profil dan keamanan akun Anda"
    />

    <!-- Tabs -->
    <x-ui.card :padding="false">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px">
                <button 
                    wire:click="$set('activeTab', 'profile')" 
                    @class([
                        'px-6 py-3 text-sm font-medium transition-colors',
                        'border-b-2 border-primary-600 text-primary-600' => $activeTab === 'profile',
                        'text-gray-500 hover:text-gray-700 hover:border-gray-300' => $activeTab !== 'profile'
                    ])
                >
                    Informasi Profil
                </button>
                <button 
                    wire:click="$set('activeTab', 'password')" 
                    @class([
                        'px-6 py-3 text-sm font-medium transition-colors',
                        'border-b-2 border-primary-600 text-primary-600' => $activeTab === 'password',
                        'text-gray-500 hover:text-gray-700 hover:border-gray-300' => $activeTab !== 'password'
                    ])
                >
                    Ubah Password
                </button>
            </nav>
        </div>

        <!-- Profile Tab -->
        @if($activeTab === 'profile')
            <form wire:submit="updateProfile" class="p-6 space-y-6">
                <x-layout.form-section 
                    title="Foto Profil"
                    description="Upload foto profil Anda untuk personalisasi akun"
                >
                    <div class="flex items-center space-x-6">
                        <div class="flex-shrink-0">
                            @if($photoPreview)
                                <x-ui.avatar 
                                    :src="$photoPreview" 
                                    :name="$name" 
                                    size="xl" 
                                    class="w-24 h-24"
                                />
                            @elseif($current_photo)
                                <x-ui.avatar 
                                    :src="Storage::url($current_photo)" 
                                    :name="$name" 
                                    size="xl" 
                                    class="w-24 h-24"
                                />
                            @else
                                <x-ui.avatar 
                                    :name="$name" 
                                    size="xl" 
                                    class="w-24 h-24"
                                />
                            @endif
                        </div>
                        <div class="flex-1">
                            <label class="cursor-pointer" for="profile-photo-upload">
                                <div class="inline-flex items-center justify-center font-semibold rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 focus:ring-blue-500 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700 px-4 py-2 text-sm">
                                    <x-ui.icon name="camera" class="w-5 h-5 mr-2" />
                                    Upload Foto
                                </div>
                                <input id="profile-photo-upload" type="file" wire:model="photo" accept="image/*" class="hidden">
                            </label>
                            @if($current_photo)
                                <x-ui.button 
                                    variant="ghost" 
                                    type="button" 
                                    wire:click="deletePhoto" 
                                    class="ml-2 text-danger-600 hover:text-danger-700"
                                >
                                    Hapus Foto
                                </x-ui.button>
                            @endif
                            <div wire:loading wire:target="photo" class="mt-2 text-sm text-gray-500">
                                <svg class="w-4 h-4 inline animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Mengupload foto...
                            </div>
                            @error('photo') 
                                <span class="block text-sm text-danger-600 mt-1">{{ $message }}</span> 
                            @enderror
                            <p class="text-xs text-gray-500 mt-1">JPG, PNG. Max 2MB</p>
                        </div>
                    </div>
                </x-layout.form-section>

                <x-layout.form-section 
                    title="Informasi Pribadi"
                    description="Update informasi pribadi Anda"
                >
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <x-ui.input
                            label="NIM"
                            name="nim"
                            wire:model="nim"
                            :required="true"
                            :error="$errors->first('nim')"
                        />

                        <x-ui.input
                            label="Nama Lengkap"
                            name="name"
                            wire:model="name"
                            :required="true"
                            :error="$errors->first('name')"
                        />

                        <x-ui.input
                            label="Email"
                            name="email"
                            type="email"
                            wire:model="email"
                            :required="true"
                            :error="$errors->first('email')"
                        />
                    </div>
                </x-layout.form-section>

                <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                    <div class="text-sm text-gray-500">
                        <strong>Role:</strong> 
                        @foreach($user->roles as $role)
                            <x-ui.badge variant="secondary" class="ml-1">
                                {{ ucfirst($role->name) }}
                            </x-ui.badge>
                        @endforeach
                    </div>
                    <x-ui.button type="submit" icon="check">
                        Simpan Perubahan
                    </x-ui.button>
                </div>
            </form>
        @endif

        <!-- Password Tab -->
        @if($activeTab === 'password')
            <form wire:submit="updatePassword" class="p-6 space-y-6">
                <div class="bg-warning-50 border border-warning-200 rounded-lg p-4">
                    <div class="flex">
                        <x-ui.icon name="exclamation-triangle" class="w-5 h-5 text-warning-600 mr-2 flex-shrink-0" />
                        <div class="text-sm text-warning-800">
                            <strong>Perhatian:</strong> Pastikan password baru Anda kuat dan mudah diingat. Gunakan kombinasi huruf besar, huruf kecil, angka, dan simbol.
                        </div>
                    </div>
                </div>

                <x-layout.form-section 
                    title="Ubah Password"
                    description="Perbarui password Anda untuk keamanan akun"
                >
                    <div class="max-w-md space-y-4">
                        <x-ui.input
                            label="Password Saat Ini"
                            name="current_password"
                            type="password"
                            wire:model="current_password"
                            :required="true"
                            :error="$errors->first('current_password')"
                        />

                        <x-ui.input
                            label="Password Baru"
                            name="new_password"
                            type="password"
                            wire:model="new_password"
                            :required="true"
                            help="Minimal 8 karakter"
                            :error="$errors->first('new_password')"
                        />

                        <x-ui.input
                            label="Konfirmasi Password Baru"
                            name="new_password_confirmation"
                            type="password"
                            wire:model="new_password_confirmation"
                            :required="true"
                        />
                    </div>
                </x-layout.form-section>

                <div class="flex justify-end pt-4 border-t border-gray-200">
                    <x-ui.button type="submit" icon="lock-closed">
                        Ubah Password
                    </x-ui.button>
                </div>
            </form>
        @endif
    </x-ui.card>
</div>
