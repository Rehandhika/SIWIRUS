<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Manajemen Anggota</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Kelola data anggota dan hak akses</p>
        </div>
        <button wire:click="create" 
            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition font-medium shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Tambah Anggota</span>
        </button>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
        <div class="flex flex-col lg:flex-row gap-4">
            {{-- Search --}}
            <div class="flex-1">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" wire:model.live.debounce.300ms="search" 
                        placeholder="Cari nama, NIM, atau email..." 
                        class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white">
                </div>
            </div>
            
            {{-- Role Filter --}}
            <div class="w-full lg:w-48">
                <select wire:model.live="roleFilter" 
                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white">
                    <option value="">Semua Role</option>
                    @foreach($this->roles as $role)
                        <option value="{{ $role->name }}">{{ ucfirst(str_replace('-', ' ', $role->name)) }}</option>
                    @endforeach
                </select>
            </div>
            
            {{-- Status Filter --}}
            <div class="w-full lg:w-40">
                <select wire:model.live="statusFilter" 
                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white">
                    <option value="">Semua Status</option>
                    <option value="active">Aktif</option>
                    <option value="inactive">Tidak Aktif</option>
                </select>
            </div>

            {{-- Clear Filters --}}
            @if($search || $roleFilter || $statusFilter)
                <button wire:click="clearFilters" 
                    class="px-4 py-2.5 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            @endif
        </div>
        
        {{-- Active Filters Info --}}
        @if($search || $roleFilter || $statusFilter)
            <div class="mt-3 flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <span>Filter aktif:</span>
                @if($search)
                    <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs">"{{ $search }}"</span>
                @endif
                @if($roleFilter)
                    <span class="px-2 py-0.5 bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 rounded text-xs">{{ ucfirst(str_replace('-', ' ', $roleFilter)) }}</span>
                @endif
                @if($statusFilter)
                    <span class="px-2 py-0.5 {{ $statusFilter === 'active' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400' }} rounded text-xs">
                        {{ $statusFilter === 'active' ? 'Aktif' : 'Tidak Aktif' }}
                    </span>
                @endif
                <span class="text-gray-400">•</span>
                <span>{{ $this->users->total() }} hasil</span>
            </div>
        @endif
    </div>

    {{-- Users List --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        {{-- Desktop Table --}}
        <div class="hidden lg:block overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Anggota</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">NIM</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($this->users as $user)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center text-white font-bold text-sm shrink-0">
                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-gray-900 dark:text-white truncate">{{ $user->name }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-mono text-sm text-gray-700 dark:text-gray-300">{{ $user->nim ?? '-' }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @forelse($user->roles->take(2) as $role)
                                        @php
                                            $roleColors = [
                                                'super-admin' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',
                                                'ketua' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
                                                'wakil-ketua' => 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400',
                                                'bph' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
                                                'anggota' => 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
                                            ];
                                            $color = $roleColors[$role->name] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300';
                                        @endphp
                                        <span class="px-2 py-0.5 rounded text-xs font-medium {{ $color }}">
                                            {{ ucfirst(str_replace('-', ' ', $role->name)) }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-gray-400 italic">Tidak ada role</span>
                                    @endforelse
                                    @if($user->roles->count() > 2)
                                        <span class="px-2 py-0.5 rounded text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                                            +{{ $user->roles->count() - 2 }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <button wire:click="toggleStatus({{ $user->id }})" 
                                    @if($user->hasRole('super-admin')) disabled @endif
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium transition
                                        {{ $user->status === 'active' 
                                            ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-200 dark:hover:bg-emerald-900/50' 
                                            : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600' }}
                                        {{ $user->hasRole('super-admin') ? 'cursor-not-allowed opacity-75' : 'cursor-pointer' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $user->status === 'active' ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                                    {{ $user->status === 'active' ? 'Aktif' : 'Tidak Aktif' }}
                                </button>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button wire:click="edit({{ $user->id }})" 
                                        class="p-2 text-gray-500 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-lg transition"
                                        title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    @if(!$user->hasRole('super-admin') && $user->id !== auth()->id())
                                        <button wire:click="delete({{ $user->id }})" 
                                            wire:confirm="Yakin ingin menghapus anggota '{{ $user->name }}'?"
                                            class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition"
                                            title="Hapus">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <p class="font-medium">Tidak ada anggota ditemukan</p>
                                <p class="text-sm mt-1">Coba ubah filter atau tambah anggota baru</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile Cards --}}
        <div class="lg:hidden divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($this->users as $user)
                <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-900/30 transition">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3 flex-1 min-w-0">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center text-white font-bold shrink-0">
                                {{ strtoupper(substr($user->name, 0, 2)) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-gray-900 dark:text-white truncate">{{ $user->name }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ $user->email }}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">NIM: {{ $user->nim ?? '-' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            <button wire:click="edit({{ $user->id }})" 
                                class="p-2 text-gray-500 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-lg transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            @if(!$user->hasRole('super-admin') && $user->id !== auth()->id())
                                <button wire:click="delete({{ $user->id }})" 
                                    wire:confirm="Yakin ingin menghapus anggota '{{ $user->name }}'?"
                                    class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Role & Status --}}
                    <div class="mt-3 flex items-center justify-between gap-2">
                        <div class="flex flex-wrap gap-1">
                            @forelse($user->roles->take(2) as $role)
                                @php
                                    $roleColors = [
                                        'super-admin' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',
                                        'ketua' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
                                        'wakil-ketua' => 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400',
                                        'bph' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
                                        'anggota' => 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
                                    ];
                                    $color = $roleColors[$role->name] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300';
                                @endphp
                                <span class="px-2 py-0.5 rounded text-xs font-medium {{ $color }}">
                                    {{ ucfirst(str_replace('-', ' ', $role->name)) }}
                                </span>
                            @empty
                                <span class="text-xs text-gray-400 italic">Tidak ada role</span>
                            @endforelse
                            @if($user->roles->count() > 2)
                                <span class="px-2 py-0.5 rounded text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                                    +{{ $user->roles->count() - 2 }}
                                </span>
                            @endif
                        </div>
                        
                        <button wire:click="toggleStatus({{ $user->id }})" 
                            @if($user->hasRole('super-admin')) disabled @endif
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium transition
                                {{ $user->status === 'active' 
                                    ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' 
                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400' }}
                                {{ $user->hasRole('super-admin') ? 'cursor-not-allowed opacity-75' : 'cursor-pointer' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $user->status === 'active' ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                            {{ $user->status === 'active' ? 'Aktif' : 'Tidak Aktif' }}
                        </button>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <p class="font-medium">Tidak ada anggota ditemukan</p>
                    <p class="text-sm mt-1">Coba ubah filter atau tambah anggota baru</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Pagination --}}
    @if($this->users->hasPages())
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 px-4 py-3">
            {{ $this->users->links() }}
        </div>
    @endif

    {{-- Modal Tambah/Edit Anggota --}}
    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" 
        x-data="{ show: true }" 
        x-show="show" 
        x-cloak
        @keydown.escape.window="$wire.closeModal()">
        
        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" 
            x-show="show"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            wire:click="closeModal">
        </div>
        
        {{-- Modal Content --}}
        <div class="flex min-h-full items-center justify-center p-4 sm:p-6">
            <div class="relative w-full max-w-2xl bg-white dark:bg-gray-800 rounded-2xl shadow-2xl transform transition-all"
                x-show="show"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                @click.stop>
                
                {{-- Header --}}
                <div class="flex items-center justify-between px-4 sm:px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $editMode ? 'Edit Anggota' : 'Tambah Anggota Baru' }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ $editMode ? 'Perbarui informasi anggota' : 'Isi data anggota baru' }}
                        </p>
                    </div>
                    <button wire:click="closeModal" 
                        class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                
                {{-- Body --}}
                <div class="px-4 sm:px-6 py-5 max-h-[calc(100vh-220px)] overflow-y-auto">
                    <form wire:submit="save" class="space-y-5">
                        {{-- Basic Info --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {{-- NIM --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                    NIM <span class="text-red-500">*</span>
                                </label>
                                <input type="text" wire:model="nim" 
                                    placeholder="Masukkan NIM"
                                    class="w-full px-4 py-2.5 border rounded-lg transition focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white
                                        {{ $errors->has('nim') ? 'border-red-500 dark:border-red-500' : 'border-gray-300 dark:border-gray-600' }}">
                                @error('nim')
                                    <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Name --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                    Nama Lengkap <span class="text-red-500">*</span>
                                </label>
                                <input type="text" wire:model="name" 
                                    placeholder="Masukkan nama lengkap"
                                    class="w-full px-4 py-2.5 border rounded-lg transition focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white
                                        {{ $errors->has('name') ? 'border-red-500 dark:border-red-500' : 'border-gray-300 dark:border-gray-600' }}">
                                @error('name')
                                    <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Email --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                    Email <span class="text-red-500">*</span>
                                </label>
                                <input type="email" wire:model="email" 
                                    placeholder="contoh@email.com"
                                    class="w-full px-4 py-2.5 border rounded-lg transition focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white
                                        {{ $errors->has('email') ? 'border-red-500 dark:border-red-500' : 'border-gray-300 dark:border-gray-600' }}">
                                @error('email')
                                    <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Password Section --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                    Password 
                                    @if($editMode)
                                        <span class="text-gray-400 font-normal">(kosongkan jika tidak diubah)</span>
                                    @else
                                        <span class="text-red-500">*</span>
                                    @endif
                                </label>
                                <input type="password" wire:model="password" 
                                    placeholder="{{ $editMode ? '••••••••' : 'Minimal 8 karakter' }}"
                                    class="w-full px-4 py-2.5 border rounded-lg transition focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white
                                        {{ $errors->has('password') ? 'border-red-500 dark:border-red-500' : 'border-gray-300 dark:border-gray-600' }}">
                                @error('password')
                                    <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                    Konfirmasi Password
                                </label>
                                <input type="password" wire:model="password_confirmation" 
                                    placeholder="Ulangi password"
                                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white">
                            </div>
                        </div>

                        {{-- Status --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Status <span class="text-red-500">*</span>
                            </label>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" wire:model="status" value="active" 
                                        class="w-4 h-4 text-primary-600 border-gray-300 focus:ring-primary-500">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Aktif</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" wire:model="status" value="inactive" 
                                        class="w-4 h-4 text-primary-600 border-gray-300 focus:ring-primary-500">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Tidak Aktif</span>
                                </label>
                            </div>
                        </div>

                        {{-- Roles --}}
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Role <span class="text-red-500">*</span>
                                    <span class="ml-2 px-2 py-0.5 rounded-full text-xs font-normal bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400">
                                        {{ count($selectedRoles) }} dipilih
                                    </span>
                                </label>
                            </div>
                            
                            <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-4 bg-gray-50 dark:bg-gray-900/50">
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                    @foreach($this->roles as $role)
                                        @php
                                            $roleColors = [
                                                'super-admin' => 'peer-checked:bg-red-100 peer-checked:border-red-500 peer-checked:text-red-700 dark:peer-checked:bg-red-900/30 dark:peer-checked:text-red-400',
                                                'ketua' => 'peer-checked:bg-amber-100 peer-checked:border-amber-500 peer-checked:text-amber-700 dark:peer-checked:bg-amber-900/30 dark:peer-checked:text-amber-400',
                                                'wakil-ketua' => 'peer-checked:bg-orange-100 peer-checked:border-orange-500 peer-checked:text-orange-700 dark:peer-checked:bg-orange-900/30 dark:peer-checked:text-orange-400',
                                                'bph' => 'peer-checked:bg-blue-100 peer-checked:border-blue-500 peer-checked:text-blue-700 dark:peer-checked:bg-blue-900/30 dark:peer-checked:text-blue-400',
                                                'anggota' => 'peer-checked:bg-gray-200 peer-checked:border-gray-500 peer-checked:text-gray-700 dark:peer-checked:bg-gray-700 dark:peer-checked:text-gray-300',
                                            ];
                                            $colorClass = $roleColors[$role->name] ?? 'peer-checked:bg-primary-100 peer-checked:border-primary-500 peer-checked:text-primary-700';
                                        @endphp
                                        <label class="relative cursor-pointer">
                                            <input type="checkbox" 
                                                wire:model.live="selectedRoles" 
                                                value="{{ $role->name }}"
                                                class="peer sr-only">
                                            <div class="flex items-center justify-center px-3 py-2.5 border-2 border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm font-medium transition-all hover:border-gray-300 dark:hover:border-gray-500 {{ $colorClass }}">
                                                {{ ucfirst(str_replace('-', ' ', $role->name)) }}
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            @error('selectedRoles')
                                <p class="mt-2 text-xs text-red-600 dark:text-red-400 flex items-center gap-1">
                                    <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </form>
                </div>
                
                {{-- Footer --}}
                <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-3 px-4 sm:px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 rounded-b-2xl">
                    <button type="button" wire:click="closeModal" 
                        class="w-full sm:w-auto px-4 py-2.5 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition font-medium text-center">
                        Batal
                    </button>
                    <button type="submit" wire:click="save"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-75 cursor-not-allowed"
                        class="w-full sm:w-auto px-5 py-2.5 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition font-medium flex items-center justify-center gap-2">
                        <span wire:loading.remove wire:target="save">
                            {{ $editMode ? 'Update Anggota' : 'Simpan Anggota' }}
                        </span>
                        <span wire:loading wire:target="save" class="flex items-center gap-2">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Menyimpan...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Loading Overlay --}}
    <div wire:loading.delay.longer wire:target="delete, toggleStatus" class="fixed inset-0 bg-black/20 backdrop-blur-sm flex items-center justify-center z-40">
        <div class="bg-white dark:bg-gray-800 rounded-xl px-6 py-4 shadow-xl flex items-center gap-3">
            <svg class="animate-spin h-5 w-5 text-primary-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span class="text-gray-700 dark:text-gray-300">Memproses...</span>
        </div>
    </div>
</div>
