<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Laporan Absensi</h1>
            <p class="text-sm text-gray-500 mt-1">
                {{ Carbon\Carbon::parse($dateFrom)->format('d M Y') }}
                @if($dateFrom !== $dateTo) - {{ Carbon\Carbon::parse($dateTo)->format('d M Y') }} @endif
            </p>
        </div>
        <x-ui.button variant="secondary" wire:click="export" wire:loading.attr="disabled">
            <x-ui.icon name="arrow-down-tray" class="w-4 h-4 mr-2" />
            <span wire:loading.remove wire:target="export">Export Excel</span>
            <span wire:loading wire:target="export">Mengunduh...</span>
        </x-ui.button>
    </div>

    {{-- Quick Date Presets --}}
    <div class="flex flex-wrap gap-2">
        @foreach(['today' => 'Hari Ini', 'yesterday' => 'Kemarin', 'week' => 'Minggu Ini', 'month' => 'Bulan Ini'] as $key => $label)
            <button wire:click="setDatePreset('{{ $key }}')" class="px-3 py-1.5 text-sm rounded-lg transition-colors {{ $datePreset === $key ? 'bg-primary-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Statistics --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl p-4 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Hadir</p>
                    <p class="text-2xl font-bold text-success-600 mt-1">{{ $this->stats['present'] }}</p>
                </div>
                <div class="w-10 h-10 bg-success-100 rounded-lg flex items-center justify-center">
                    <span class="w-3 h-3 bg-success-500 rounded-full"></span>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Terlambat</p>
                    <p class="text-2xl font-bold text-warning-600 mt-1">{{ $this->stats['late'] }}</p>
                </div>
                <div class="w-10 h-10 bg-warning-100 rounded-lg flex items-center justify-center">
                    <span class="w-3 h-3 bg-warning-500 rounded-full"></span>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Tidak Hadir</p>
                    <p class="text-2xl font-bold text-danger-600 mt-1">{{ $this->stats['absent'] }}</p>
                </div>
                <div class="w-10 h-10 bg-danger-100 rounded-lg flex items-center justify-center">
                    <span class="w-3 h-3 bg-danger-500 rounded-full"></span>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">% Kehadiran</p>
                    <p class="text-2xl font-bold text-primary-600 mt-1">{{ $this->stats['attendance_rate'] }}%</p>
                </div>
                <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center">
                    <x-ui.icon name="chart-bar" class="w-5 h-5 text-primary-600" />
                </div>
            </div>
        </div>
    </div>

    {{-- Weekly Schedule Summary --}}
    <div class="w-full block">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="bg-primary-100 p-2 rounded-lg">
                        <x-ui.icon name="calendar" class="w-5 h-5 text-primary-600" />
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Jadwal Minggu Ini</h2>
                        <p class="text-sm text-gray-500">Ringkasan jadwal dan kehadiran anggota</p>
                    </div>
                </div>
            </div>

            <div class="divide-y divide-gray-200">
                @forelse($this->weeklyScheduleData as $date => $assignments)
                    <div class="bg-white">
                        {{-- Date Header --}}
                        <div class="px-6 py-3 bg-gray-50/30 flex items-center gap-2 border-b border-gray-100">
                            <x-ui.icon name="calendar-days" class="w-4 h-4 text-gray-400" />
                            <h3 class="text-sm font-semibold text-gray-900">
                                {{ \Carbon\Carbon::parse($date)->locale('id')->isoFormat('dddd, D MMMM Y') }}
                            </h3>
                            <span class="ml-auto text-xs font-medium text-gray-500 bg-white px-2 py-0.5 rounded border border-gray-200">
                                {{ count($assignments) }} Sesi
                            </span>
                        </div>

                        {{-- Assignments List --}}
                        <div class="divide-y divide-gray-100">
                            @foreach($assignments as $item)
                                <div class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition-colors group">
                                    <div class="flex items-center gap-4">
                                        {{-- User Photo --}}
                                        <div class="shrink-0 relative">
                                            @if($item['user_photo'])
                                                <img src="{{ $item['user_photo'] }}" class="w-12 h-12 rounded-full object-cover ring-2 ring-white shadow-sm">
                                            @else
                                                <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center ring-2 ring-white shadow-sm text-gray-500 font-bold text-lg">
                                                    {{ substr($item['user_name'], 0, 1) }}
                                                </div>
                                            @endif
                                            <div class="absolute -bottom-1 -right-1 bg-white rounded-full p-0.5 shadow-sm">
                                                <div class="w-4 h-4 rounded-full flex items-center justify-center text-[10px] font-bold text-white {{ match($item['session']) { 1 => 'bg-blue-500', 2 => 'bg-purple-500', 3 => 'bg-pink-500', default => 'bg-gray-500' } }}">
                                                    {{ $item['session'] }}
                                                </div>
                                            </div>
                                        </div>

                                        {{-- User Info --}}
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900 group-hover:text-primary-600 transition-colors">
                                                {{ $item['user_name'] }}
                                            </h4>
                                            <div class="flex items-center gap-3 mt-1">
                                                <div class="flex items-center gap-1.5 text-xs text-gray-500">
                                                    <x-ui.icon name="clock" class="w-3.5 h-3.5" />
                                                    <span>{{ $item['time_range'] }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Status --}}
                                    <div class="flex flex-col items-end gap-1">
                                        @php
                                            $statusConfig = match($item['status_color']) {
                                                'success' => ['bg' => 'bg-green-50', 'text' => 'text-green-700', 'border' => 'border-green-200', 'icon' => 'check-circle'],
                                                'warning' => ['bg' => 'bg-yellow-50', 'text' => 'text-yellow-800', 'border' => 'border-yellow-200', 'icon' => 'clock'],
                                                'danger' => ['bg' => 'bg-red-50', 'text' => 'text-red-700', 'border' => 'border-red-200', 'icon' => 'x-circle'],
                                                'info' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'border' => 'border-blue-200', 'icon' => 'information-circle'],
                                                default => ['bg' => 'bg-gray-50', 'text' => 'text-gray-600', 'border' => 'border-gray-200', 'icon' => 'minus-circle'],
                                            };
                                        @endphp
                                        <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-full border {{ $statusConfig['bg'] }} {{ $statusConfig['border'] }}">
                                            <x-ui.icon name="{{ $statusConfig['icon'] }}" class="w-3.5 h-3.5 {{ $statusConfig['text'] }}" />
                                            <span class="text-xs font-semibold {{ $statusConfig['text'] }}">
                                                {{ $item['status_label'] }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="p-12 text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-50 mb-4">
                            <x-ui.icon name="calendar" class="w-8 h-8 text-gray-300" />
                        </div>
                        <h3 class="text-lg font-medium text-gray-900">Tidak ada jadwal</h3>
                        <p class="text-gray-500 mt-1">Belum ada jadwal yang dipublikasikan untuk minggu ini.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <x-ui.card>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <x-ui.input type="date" name="dateFrom" label="Dari" wire:model.live="dateFrom" />
            <x-ui.input type="date" name="dateTo" label="Sampai" wire:model.live="dateTo" />
            <x-ui.select name="filterStatus" label="Status" wire:model.live="filterStatus" :options="['' => 'Semua', 'present' => 'Hadir', 'late' => 'Terlambat', 'absent' => 'Tidak Hadir', 'excused' => 'Izin']" />
            <x-ui.input type="text" name="search" label="Cari" placeholder="Nama / NIM..." wire:model.live.debounce.300ms="search" />
        </div>
    </x-ui.card>

    {{-- Table --}}
    <x-ui.card padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Anggota</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Check-in</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Check-out</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Foto</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($attendances as $attendance)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    @if($attendance->user?->photo)
                                        <img src="{{ Storage::url($attendance->user->photo) }}" alt="" class="w-8 h-8 rounded-full object-cover" loading="lazy">
                                    @else
                                        <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center">
                                            <span class="text-xs font-medium text-gray-600">{{ substr($attendance->user?->name ?? '?', 0, 1) }}</span>
                                        </div>
                                    @endif
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ $attendance->user?->name ?? '-' }}</p>
                                        <p class="text-xs text-gray-500">{{ $attendance->user?->nim ?? '-' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <p class="text-sm text-gray-900">{{ $attendance->date->format('d/m/Y') }}</p>
                                <p class="text-xs text-gray-500">{{ $attendance->date->locale('id')->dayName }}</p>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                {{ $attendance->check_in ? Carbon\Carbon::parse($attendance->check_in)->format('H:i') : '-' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                {{ $attendance->check_out ? Carbon\Carbon::parse($attendance->check_out)->format('H:i') : '-' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex flex-col gap-1">
                                    <span class="inline-flex items-center gap-1.5 text-sm">
                                        <span class="w-2 h-2 rounded-full {{ match($attendance->status) { 'present' => 'bg-success-500', 'late' => 'bg-warning-500', 'absent' => 'bg-danger-500', 'excused' => 'bg-info-500', default => 'bg-gray-400' } }}"></span>
                                        {{ match($attendance->status) { 'present' => 'Hadir', 'late' => 'Terlambat', 'absent' => 'Tidak Hadir', 'excused' => 'Izin', default => '-' } }}
                                    </span>
                                    @if($attendance->status === 'late' && $attendance->late_category)
                                        <span class="text-[10px] font-bold text-warning-700 bg-warning-50 px-1.5 py-0.5 rounded border border-warning-200 w-fit">
                                            KAT {{ $attendance->late_category }} ({{ $attendance->late_minutes }}m)
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if(!isset($attendance->is_virtual) && $attendance->check_in_photo)
                                    <button wire:click="viewPhoto('{{ $attendance->check_in_photo_url }}', '{{ $attendance->user?->name }}')" class="group relative">
                                        <img src="{{ $attendance->check_in_photo_url }}" alt="" class="w-10 h-10 rounded-lg object-cover border border-gray-200 group-hover:ring-2 group-hover:ring-primary-500 transition-all" loading="lazy">
                                    </button>
                                @else
                                    <span class="text-xs text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center gap-1">
                                    @if(!isset($attendance->is_virtual))
                                        <button wire:click="showDetail({{ $attendance->id }})" class="p-1.5 text-gray-500 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors" title="Detail">
                                            <x-ui.icon name="eye" class="w-4 h-4" />
                                        </button>
                                        <button wire:click="openEdit({{ $attendance->id }})" class="p-1.5 text-gray-500 hover:text-warning-600 hover:bg-warning-50 rounded-lg transition-colors" title="Edit">
                                            <x-ui.icon name="pencil" class="w-4 h-4" />
                                        </button>
                                    @else
                                        <span class="text-xs text-gray-400 italic">Sistem</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center">
                                <x-layout.empty-state icon="clipboard-document-list" title="Tidak ada data absensi" description="Belum ada data absensi untuk periode yang dipilih" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

    @if($attendances->hasPages())
        <div class="mt-4">{{ $attendances->links() }}</div>
    @endif

    {{-- Photo Modal --}}
    @if($showPhotoModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data x-on:keydown.escape.window="$wire.closePhotoModal()">
            <div class="fixed inset-0 bg-black/70" wire:click="closePhotoModal"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative bg-white rounded-xl shadow-2xl max-w-2xl w-full overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">
                        <h3 class="text-sm font-medium text-gray-900">Foto Check-in: {{ $selectedUserName }}</h3>
                        <button wire:click="closePhotoModal" class="text-gray-400 hover:text-gray-600"><x-ui.icon name="x" class="w-5 h-5" /></button>
                    </div>
                    <div class="p-4">
                        <img src="{{ $selectedPhoto }}" alt="" class="w-full h-auto rounded-lg">
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Detail Modal --}}
    @if($showDetailModal && $detailData)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data x-on:keydown.escape.window="$wire.closeDetailModal()">
            <div class="fixed inset-0 bg-black/50" wire:click="closeDetailModal"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative bg-white rounded-xl shadow-2xl max-w-lg w-full overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-base font-semibold text-gray-900">Detail Absensi</h3>
                        <button wire:click="closeDetailModal" class="text-gray-400 hover:text-gray-600"><x-ui.icon name="x" class="w-5 h-5" /></button>
                    </div>
                    <div class="p-4 space-y-4 max-h-[70vh] overflow-y-auto">
                        {{-- User Info --}}
                        <div class="flex items-center gap-4 p-3 bg-gray-50 rounded-lg">
                            @if($detailData['user_photo'])
                                <img src="{{ $detailData['user_photo'] }}" alt="" class="w-14 h-14 rounded-full object-cover">
                            @else
                                <div class="w-14 h-14 rounded-full bg-gray-300 flex items-center justify-center">
                                    <span class="text-xl font-medium text-gray-600">{{ substr($detailData['user_name'], 0, 1) }}</span>
                                </div>
                            @endif
                            <div>
                                <p class="font-semibold text-gray-900">{{ $detailData['user_name'] }}</p>
                                <p class="text-sm text-gray-500">{{ $detailData['user_nim'] }}</p>
                            </div>
                        </div>

                        {{-- Attendance Info --}}
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div class="p-3 bg-gray-50 rounded-lg">
                                <p class="text-xs text-gray-500 mb-1">Tanggal</p>
                                <p class="font-medium text-gray-900">{{ $detailData['day'] }}, {{ $detailData['date'] }}</p>
                            </div>
                            <div class="p-3 bg-gray-50 rounded-lg">
                                <p class="text-xs text-gray-500 mb-1">Status</p>
                                <div class="flex flex-col gap-1">
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full {{ match($detailData['status']) { 'present' => 'bg-success-500', 'late' => 'bg-warning-500', 'absent' => 'bg-danger-500', 'excused' => 'bg-info-500', default => 'bg-gray-400' } }}"></span>
                                        <span class="font-medium text-gray-900">{{ match($detailData['status']) { 'present' => 'Hadir', 'late' => 'Terlambat', 'absent' => 'Tidak Hadir', 'excused' => 'Izin', default => '-' } }}</span>
                                    </span>
                                    @if($detailData['status'] === 'late' && $detailData['late_category'])
                                        <span class="text-[10px] font-bold text-warning-700 bg-warning-100 px-1.5 py-0.5 rounded border border-warning-200 w-fit">
                                            KAT {{ $detailData['late_category'] }} ({{ $detailData['late_minutes'] }} menit)
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="p-3 bg-gray-50 rounded-lg">
                                <p class="text-xs text-gray-500 mb-1">Check-in</p>
                                <p class="font-medium text-gray-900">{{ $detailData['check_in'] ?? '-' }}</p>
                            </div>
                            <div class="p-3 bg-gray-50 rounded-lg">
                                <p class="text-xs text-gray-500 mb-1">Check-out</p>
                                <p class="font-medium text-gray-900">{{ $detailData['check_out'] ?? '-' }}</p>
                            </div>
                            @if($detailData['work_hours'])
                            <div class="p-3 bg-gray-50 rounded-lg col-span-2">
                                <p class="text-xs text-gray-500 mb-1">Total Jam Kerja</p>
                                <p class="font-medium text-gray-900">{{ $detailData['work_hours'] }} jam</p>
                            </div>
                            @endif
                        </div>

                        {{-- Photo --}}
                        @if($detailData['check_in_photo'])
                        <div>
                            <p class="text-xs text-gray-500 mb-2">Foto Check-in</p>
                            <img src="{{ $detailData['check_in_photo'] }}" alt="" class="w-full h-48 object-cover rounded-lg border border-gray-200">
                        </div>
                        @endif
                    </div>
                    <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                        <x-ui.button variant="white" wire:click="closeDetailModal" class="w-full">Tutup</x-ui.button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Edit Modal --}}
    @if($showEditModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data x-on:keydown.escape.window="$wire.closeEditModal()">
            <div class="fixed inset-0 bg-black/50" wire:click="closeEditModal"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative bg-white rounded-xl shadow-2xl max-w-md w-full overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-base font-semibold text-gray-900">Edit Absensi</h3>
                        <button wire:click="closeEditModal" class="text-gray-400 hover:text-gray-600"><x-ui.icon name="x" class="w-5 h-5" /></button>
                    </div>
                    <div class="p-4 space-y-4">
                        <x-ui.select name="editStatus" label="Status" wire:model="editStatus" :options="['present' => 'Hadir', 'late' => 'Terlambat', 'absent' => 'Tidak Hadir', 'excused' => 'Izin']" />
                        @error('editStatus')<p class="text-sm text-red-600">{{ $message }}</p>@enderror

                        <x-ui.input type="time" name="editCheckIn" label="Waktu Check-in" wire:model="editCheckIn" />
                        @error('editCheckIn')<p class="text-sm text-red-600">{{ $message }}</p>@enderror

                        <x-ui.input type="time" name="editCheckOut" label="Waktu Check-out" wire:model="editCheckOut" />
                        @error('editCheckOut')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="px-4 py-3 border-t border-gray-200 bg-gray-50 flex gap-2">
                        <x-ui.button variant="white" wire:click="closeEditModal" class="flex-1">Batal</x-ui.button>
                        <x-ui.button variant="primary" wire:click="saveEdit" class="flex-1">
                            <span wire:loading.remove wire:target="saveEdit">Simpan</span>
                            <span wire:loading wire:target="saveEdit">Menyimpan...</span>
                        </x-ui.button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
