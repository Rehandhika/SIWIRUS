<div class="space-y-4 sm:space-y-6">
    {{-- Welcome Header --}}
    <div class="bg-gradient-to-r from-primary-600 to-primary-700 rounded-xl overflow-hidden shadow-lg">
        <div class="p-4 sm:p-6 text-white">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm shrink-0">
                        @if(auth()->check())
                            <span class="text-white font-bold text-xl sm:text-2xl">{{ substr(auth()->user()->name, 0, 1) }}</span>
                        @else
                            <span class="text-white font-bold text-xl">?</span>
                        @endif
                    </div>
                    <div class="min-w-0">
                        @if(auth()->check())
                            <h1 class="text-lg sm:text-xl font-bold truncate">Halo, {{ auth()->user()->name }}!</h1>
                            <p class="text-primary-100 text-xs sm:text-sm mt-0.5">
                                {{ auth()->user()->nim ?? '-' }} • 
                                {{ auth()->user()->roles?->pluck('name')?->join(', ') ?? 'User' }}
                            </p>
                        @else
                            <h1 class="text-lg sm:text-xl font-bold">Selamat datang di SIWIRUS</h1>
                            <p class="text-primary-100 text-sm">Silakan login untuk melanjutkan</p>
                        @endif
                    </div>
                </div>
                <div class="text-left sm:text-right bg-white/10 rounded-lg px-3 py-2 sm:px-4 sm:py-3">
                    <p class="text-xs text-primary-100">{{ now()->translatedFormat('l') }}</p>
                    <p class="text-sm sm:text-base font-semibold">{{ now()->translatedFormat('d F Y') }}</p>
                    <p class="text-xl sm:text-2xl font-bold mt-0.5" x-data x-init="
                        setInterval(() => {
                            $el.textContent = new Date().toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit', second: '2-digit'})
                        }, 1000)
                    ">{{ now()->format('H:i:s') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- User Stats Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-3 sm:p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Kehadiran</p>
                    <p class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white">
                        {{ $this->userStats['monthlyAttendance']['present'] }}/{{ $this->userStats['monthlyAttendance']['total'] }}
                    </p>
                    <p class="text-xs text-gray-400">bulan ini</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg p-3 sm:p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Terlambat</p>
                    <p class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white">
                        {{ $this->userStats['monthlyAttendance']['late'] }}
                    </p>
                    <p class="text-xs text-gray-400">kali</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg p-3 sm:p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-red-100 dark:bg-red-900/30 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Penalti</p>
                    <p class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white">
                        {{ $this->userStats['penalties']['count'] }}
                    </p>
                    <p class="text-xs text-gray-400">{{ $this->userStats['penalties']['points'] }} poin</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg p-3 sm:p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Notifikasi</p>
                    <p class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white">
                        {{ $this->userStats['notificationCount'] }}
                    </p>
                    <p class="text-xs text-gray-400">belum dibaca</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Admin Stats --}}
    @if($this->isAdmin)
    {{-- Today's Attendance Summary Widget --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <h2 class="font-semibold text-sm text-gray-900 dark:text-white">Ringkasan Kehadiran Hari Ini</h2>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">Hadir</p>
                            <p class="text-2xl font-bold text-emerald-900 dark:text-emerald-100">{{ $this->todayAttendanceSummary['present'] }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-amber-600 dark:text-amber-400 font-medium">Terlambat</p>
                            <p class="text-2xl font-bold text-amber-900 dark:text-amber-100">{{ $this->todayAttendanceSummary['late'] }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-red-100 dark:bg-red-900/50 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-red-600 dark:text-red-400 font-medium">Tidak Hadir</p>
                            <p class="text-2xl font-bold text-red-900 dark:text-red-100">{{ $this->todayAttendanceSummary['absent'] }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-blue-600 dark:text-blue-400 font-medium">Izin</p>
                            <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ $this->todayAttendanceSummary['excused'] }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Pending Leave Requests Widget --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h2 class="font-semibold text-sm text-gray-900 dark:text-white">Pengajuan Izin Menunggu Persetujuan</h2>
            @if($this->pendingLeaveRequests->count() > 0)
                <a href="{{ route('admin.leave.approval') }}" class="text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                    Lihat Semua
                </a>
            @endif
        </div>
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($this->pendingLeaveRequests as $leave)
                <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $leave->user->name }}</p>
                                <span class="px-2 py-0.5 text-xs font-medium rounded 
                                    {{ $leave->leave_type === 'sick' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' }}">
                                    {{ $leave->leave_type === 'sick' ? 'Sakit' : 'Izin' }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                                {{ $leave->user->nim }}
                            </p>
                            <p class="text-xs text-gray-600 dark:text-gray-300 mb-2">
                                {{ $leave->start_date->translatedFormat('d M Y') }} - {{ $leave->end_date->translatedFormat('d M Y') }}
                                <span class="text-gray-400">({{ $leave->start_date->diffInDays($leave->end_date) + 1 }} hari)</span>
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2">{{ $leave->reason }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-xs text-gray-400 dark:text-gray-500">{{ $leave->created_at->diffForHumans() }}</p>
                            <a href="{{ route('admin.leave.approval') }}" 
                                class="inline-flex items-center gap-1 mt-2 text-xs font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                                Tinjau
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-8">
                    <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada pengajuan izin yang menunggu</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Penalty Threshold Warning Widget --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <h2 class="font-semibold text-sm text-gray-900 dark:text-white">Peringatan Threshold Penalti</h2>
                @if($this->usersApproachingThreshold->count() > 0)
                    <span class="px-2 py-0.5 text-xs font-medium rounded bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                        {{ $this->usersApproachingThreshold->count() }}
                    </span>
                @endif
            </div>
            @if($this->usersApproachingThreshold->count() > 0)
                <a href="{{ route('admin.penalty.manage') }}" class="text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                    Lihat Semua
                </a>
            @endif
        </div>
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($this->usersApproachingThreshold as $user)
                <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $user->name }}</p>
                                @if($user->total_points >= 50)
                                    <span class="px-2 py-0.5 text-xs font-medium rounded bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                        Suspended
                                    </span>
                                @elseif($user->total_points >= 40)
                                    <span class="px-2 py-0.5 text-xs font-medium rounded bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                        Mendekati Threshold
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $user->nim }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="flex items-center gap-2">
                                <div class="text-right">
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Total Poin</p>
                                    <p class="text-lg font-bold {{ $user->total_points >= 50 ? 'text-red-600 dark:text-red-400' : 'text-amber-600 dark:text-amber-400' }}">
                                        {{ $user->total_points }}
                                    </p>
                                </div>
                                <div class="w-16 h-16">
                                    <svg class="transform -rotate-90" viewBox="0 0 36 36">
                                        <circle cx="18" cy="18" r="16" fill="none" class="stroke-gray-200 dark:stroke-gray-700" stroke-width="3"></circle>
                                        <circle cx="18" cy="18" r="16" fill="none" 
                                            class="{{ $user->total_points >= 50 ? 'stroke-red-500' : 'stroke-amber-500' }}" 
                                            stroke-width="3" 
                                            stroke-dasharray="{{ ($user->total_points / 50) * 100 }}, 100"
                                            stroke-linecap="round"></circle>
                                    </svg>
                                </div>
                            </div>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ round(($user->total_points / 50) * 100) }}% dari threshold</p>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-8">
                    <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada user yang mendekati threshold</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Semua user dalam kondisi baik</p>
                </div>
            @endforelse
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <h2 class="font-semibold text-sm text-gray-900 dark:text-white">Statistik Admin Hari Ini</h2>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="border-l-4 border-emerald-500 pl-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Kehadiran</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $this->adminStats['todayAttendance']['present'] }}/{{ $this->adminStats['todayAttendance']['total'] }}
                    </p>
                </div>
                <div class="border-l-4 border-blue-500 pl-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Penjualan</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">{{ format_currency($this->adminStats['todaySales']) }}</p>
                    <p class="text-xs text-gray-400">{{ $this->adminStats['todayTransactions'] }} transaksi</p>
                </div>
                <div class="border-l-4 border-amber-500 pl-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Stok Rendah</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">{{ $this->adminStats['lowStockProducts'] }}</p>
                    <p class="text-xs text-gray-400">produk</p>
                </div>
                <div class="border-l-4 border-red-500 pl-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Persetujuan</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">{{ $this->adminStats['pendingLeaves'] + $this->adminStats['pendingSwaps'] }}</p>
                    <p class="text-xs text-gray-400">{{ $this->adminStats['pendingLeaves'] }} cuti, {{ $this->adminStats['pendingSwaps'] }} tukar</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Main Content --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {{-- Today's Schedule --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <h2 class="font-semibold text-sm text-gray-900 dark:text-white">Jadwal Hari Ini</h2>
            </div>
            <div class="p-4">
                @if($this->userStats['todaySchedule'])
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-medium text-blue-900 dark:text-blue-100">Sesi {{ $this->userStats['todaySchedule']->session }}</h4>
                                <p class="text-sm text-blue-700 dark:text-blue-300 mt-0.5">
                                    {{ $this->userStats['todaySchedule']->date->translatedFormat('d F Y') }}
                                </p>
                                <a href="{{ route('admin.attendance.check-in-out') }}" 
                                    class="inline-flex items-center gap-1 mt-3 text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                                    Check-in Sekarang
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada jadwal hari ini</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Recent Notifications --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h2 class="font-semibold text-sm text-gray-900 dark:text-white">Notifikasi Terbaru</h2>
                @if($this->userStats['notifications']->count() > 0)
                    <a href="{{ route('admin.notifications.index') }}" class="text-xs text-primary-600 hover:text-primary-700">Lihat Semua</a>
                @endif
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($this->userStats['notifications'] as $notification)
                    <div class="p-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <div class="flex items-start gap-3">
                            <div class="w-2 h-2 bg-blue-500 rounded-full mt-1.5 shrink-0"></div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $notification->title }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-2">{{ $notification->message }}</p>
                                <p class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada notifikasi baru</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Upcoming Schedules --}}
    @if($this->userStats['upcomingSchedules']->count() > 0)
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <h2 class="font-semibold text-sm text-gray-900 dark:text-white">Jadwal Mendatang (7 Hari)</h2>
        </div>
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            @foreach($this->userStats['upcomingSchedules'] as $schedule)
                <div class="p-3 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                    <div class="flex items-center gap-3">
                        <div class="w-12 text-center shrink-0">
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $schedule->date->translatedFormat('D') }}</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $schedule->date->format('d') }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">Sesi {{ $schedule->session }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $schedule->date->translatedFormat('F Y') }}</p>
                        </div>
                    </div>
                    <span class="px-2 py-0.5 text-xs font-medium rounded bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                        {{ ucfirst($schedule->status) }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
