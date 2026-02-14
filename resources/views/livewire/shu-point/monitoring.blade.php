<div class="space-y-6">
    <x-layout.page-header
        title="Poin SHU - Monitoring & Pencairan"
        description="Pantau saldo poin, kelola mahasiswa, dan catat pencairan poin"
    />

    {{-- Tab Navigation --}}
    <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="flex space-x-8" aria-label="Tabs">
            <button
                wire:click="setTab('students')"
                class="py-4 px-1 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'students' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}"
            >
                <span class="flex items-center gap-2">
                    <x-ui.icon name="users" class="w-5 h-5" />
                    Data Mahasiswa
                </span>
            </button>
            <button
                wire:click="setTab('redemptions')"
                class="py-4 px-1 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'redemptions' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}"
            >
                <span class="flex items-center gap-2">
                    <x-ui.icon name="currency-dollar" class="w-5 h-5" />
                    Pencairan Poin
                </span>
            </button>
        </nav>
    </div>

    {{-- Students Tab --}}
    @if($activeTab === 'students')
        <x-ui.card>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-6">
                <div class="flex-1 max-w-md">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <x-ui.icon name="magnifying-glass" class="h-5 w-5 text-gray-400" />
                        </div>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Cari NIM atau nama..."
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                        >
                    </div>
                </div>
                <div class="flex gap-2">
                    @can('kelola_poin_shu')
                        <x-ui.button variant="secondary" wire:click="exportStudentsExcel" icon="arrow-down-tray">
                            Export Excel
                        </x-ui.button>
                    @endcan
                    @can('kelola_poin_shu')
                        <x-ui.button variant="primary" wire:click="createStudent" icon="plus">
                            Tambah Mahasiswa
                        </x-ui.button>
                    @endcan
                </div>
            </div>

            <div class="overflow-x-auto -mx-4 sm:mx-0">
                <div class="inline-block min-w-full align-middle">
                    <div class="overflow-hidden border border-gray-200 rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" wire:click="sortBy('nim')">
                                        NIM
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" wire:click="sortBy('full_name')">
                                        Nama
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" wire:click="sortBy('points_balance')">
                                        Saldo Poin
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" wire:click="sortBy('created_at')">
                                        Dibuat
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($this->students as $student)
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 font-mono">
                                            {{ $student->nim }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $student->full_name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold text-primary-600">
                                            {{ number_format($student->points_balance, 0, ',', '.') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $student->created_at?->format('d/m/Y H:i') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex justify-end gap-2">
                                                <a href="{{ route('admin.poin-shu.student', $student) }}" class="text-primary-600 hover:text-primary-900">
                                                    Detail
                                                </a>
                                                @can('kelola_poin_shu')
                                                    <span class="text-gray-300">|</span>
                                                    <button type="button" wire:click="editStudent({{ $student->id }})" class="text-gray-600 hover:text-gray-900">
                                                        Ubah
                                                    </button>
                                                    <span class="text-gray-300">|</span>
                                                    <button type="button" wire:click="deleteStudent({{ $student->id }})" wire:confirm="Hapus mahasiswa ini?" class="text-red-600 hover:text-red-900">
                                                        Hapus
                                                    </button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                                            <div class="flex flex-col items-center justify-center">
                                                <x-ui.icon name="users" class="w-10 h-10 text-gray-300 mb-2" />
                                                <p>Tidak ada data mahasiswa ditemukan</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                {{ $this->students->links() }}
            </div>
        </x-ui.card>
    @endif

    {{-- Redemptions Tab --}}
    @if($activeTab === 'redemptions')
        {{-- Redemption Form --}}
        @can('kelola_poin_shu')
            <x-ui.card>
                <x-layout.form-section
                    title="Form Pencairan Poin"
                    description="Catat pencairan poin untuk mahasiswa"
                >
                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                        <div>
                            <label for="studentNim" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">NIM</label>
                            <input
                                id="studentNim"
                                type="text"
                                wire:model.defer="studentNim"
                                inputmode="numeric"
                                maxlength="9"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                placeholder="NIM mahasiswa"
                            >
                            @error('studentNim') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="points" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Poin Dicairkan</label>
                            <input
                                id="points"
                                type="number"
                                min="1"
                                wire:model.defer="points"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            >
                            @error('points') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="cash_amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nominal (Opsional)</label>
                            <input
                                id="cash_amount"
                                type="number"
                                min="0"
                                wire:model.defer="cash_amount"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            >
                            @error('cash_amount') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="lg:flex lg:items-end">
                            <x-ui.button variant="danger" wire:click="redeem" class="w-full justify-center">
                                Simpan Pencairan
                            </x-ui.button>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Catatan (Opsional)</label>
                        <input
                            id="notes"
                            type="text"
                            wire:model.defer="notes"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                        >
                        @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </x-layout.form-section>
            </x-ui.card>
        @endcan

        {{-- Redemptions List --}}
        <x-ui.card>
            <x-layout.form-section
                title="Riwayat Pencairan"
                description="Daftar pencairan poin yang telah dicatat"
            >
                <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 flex-1">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-gray-700 uppercase mb-1">Cari</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <x-ui.icon name="magnifying-glass" class="h-4 w-4 text-gray-400" />
                                </div>
                                <input
                                    type="text"
                                    wire:model.live.debounce.300ms="redemptionSearch"
                                    placeholder="Cari NIM atau nama..."
                                    class="block w-full pl-9 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                >
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 uppercase mb-1">Dari</label>
                            <input
                                type="date"
                                wire:model.live="dateFrom"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md leading-5 bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                            >
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 uppercase mb-1">Sampai</label>
                            <input
                                type="date"
                                wire:model.live="dateTo"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md leading-5 bg-white focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                            >
                        </div>
                    </div>
                    <div>
                        @can('kelola_poin_shu')
                            <x-ui.button variant="secondary" wire:click="exportRedemptionsExcel" icon="arrow-down-tray">
                                Export Excel
                            </x-ui.button>
                        @endcan
                    </div>
                </div>

                <div class="overflow-x-auto -mx-4 sm:mx-0">
                    <div class="inline-block min-w-full align-middle">
                        <div class="overflow-hidden border border-gray-200 rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NIM</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Poin</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Nominal</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Catatan</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse($this->redemptions as $trx)
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $trx->created_at?->format('d/m/Y H:i') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-mono">
                                                {{ $trx->student?->nim }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $trx->student?->full_name }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-red-600">
                                                {{ number_format(abs($trx->points), 0, ',', '.') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
                                                {{ $trx->cash_amount ? number_format($trx->cash_amount, 0, ',', '.') : '-' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $trx->notes ?: '-' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                                                <div class="flex flex-col items-center justify-center">
                                                    <x-ui.icon name="document-text" class="w-10 h-10 text-gray-300 mb-2" />
                                                    <p>Tidak ada riwayat pencairan</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    {{ $this->redemptions->links() }}
                </div>
            </x-layout.form-section>
        </x-ui.card>
    @endif

    {{-- Student Modal --}}
    <div
        x-data="{ show: @entangle('showStudentModal') }"
        x-show="show"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        style="display: none;"
    >
        <!-- Backdrop -->
        <div 
            x-show="show"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
            wire:click="closeStudentModal"
        ></div>

        <!-- Modal Panel -->
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div
                x-show="show"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg"
            >
                <!-- Header -->
                <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4 border-b border-gray-100">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold leading-6 text-gray-900">
                            {{ $editMode ? 'Ubah Mahasiswa' : 'Tambah Mahasiswa' }}
                        </h3>
                        <button wire:click="closeStudentModal" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                            <x-ui.icon name="x-mark" class="h-6 w-6" />
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <div class="px-4 py-5 sm:p-6">
                    <div class="space-y-4">
                        <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <x-ui.icon name="information-circle" class="h-5 w-5 text-blue-400" />
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700">
                                        NIM harus terdiri dari 9 digit angka dan belum terdaftar di sistem.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="modal-nim" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">NIM</label>
                            <input
                                id="modal-nim"
                                type="text"
                                wire:model.defer="nim"
                                inputmode="numeric"
                                maxlength="9"
                                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-primary-500 focus:border-primary-500"
                                placeholder="Contoh: 123456789"
                            >
                            @error('nim') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="modal-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Lengkap</label>
                            <input
                                id="modal-name"
                                type="text"
                                wire:model.defer="full_name"
                                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-primary-500 focus:border-primary-500"
                                placeholder="Nama lengkap mahasiswa"
                            >
                            @error('full_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <button 
                        type="button" 
                        wire:click="saveStudent" 
                        class="inline-flex w-full justify-center rounded-xl bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 sm:ml-3 sm:w-auto"
                    >
                        Simpan
                    </button>
                    <button 
                        type="button" 
                        wire:click="closeStudentModal" 
                        class="mt-3 inline-flex w-full justify-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto"
                    >
                        Batal
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
