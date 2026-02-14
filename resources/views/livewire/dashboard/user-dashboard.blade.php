<div class="space-y-6">
    {{-- Hero Section / Greeting --}}
    <div class="bg-gradient-to-br from-primary-600 to-primary-800 rounded-2xl p-6 text-white shadow-xl relative overflow-hidden">
        {{-- Background Pattern --}}
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -mr-16 -mt-16 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 bg-black/10 rounded-full -ml-10 -mb-10 blur-2xl"></div>

        <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-4 w-full md:w-auto">
                <div class="w-16 h-16 bg-white/20 backdrop-blur-md rounded-full flex items-center justify-center border-2 border-white/30 shadow-inner">
                    <span class="text-2xl font-bold">{{ substr($this->user->name, 0, 1) }}</span>
                </div>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Halo, {{ $this->user->name }}!</h1>
                    <p class="text-primary-100 text-sm mt-1 flex items-center gap-2">
                        <span class="opacity-75">{{ $this->user->nim }}</span>
                        <span class="w-1 h-1 bg-white rounded-full"></span>
                        <span class="font-medium bg-white/20 px-2 py-0.5 rounded text-xs">
                            {{ $this->user->roles->first()->name ?? 'Anggota' }}
                        </span>
                    </p>
                </div>
            </div>

            {{-- Quick Stats & Clock --}}
            <div class="flex items-center gap-6 bg-white/10 p-3 rounded-xl backdrop-blur-sm border border-white/10">
                <div class="text-center px-2">
                    <p class="text-xs text-primary-200 uppercase tracking-wider font-medium">Jam Sekarang</p>
                    <p class="text-xl font-bold font-mono" x-data x-init="setInterval(() => $el.innerText = new Date().toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit'}), 1000)">
                        {{ now()->format('H:i') }}
                    </p>
                </div>
                <div class="w-px h-8 bg-white/20"></div>
                <div class="text-center px-2">
                    <p class="text-xs text-primary-200 uppercase tracking-wider font-medium">Poin Penalti</p>
                    <p class="text-xl font-bold {{ $this->userStats['penalty'] > 0 ? 'text-red-300' : 'text-emerald-300' }}">
                        {{ $this->userStats['penalty'] }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-1 gap-6">
        {{-- Left Column: Next Shift & Weekly Schedule --}}
        <div class="space-y-6">
            
            {{-- Next Shift Card --}}
            <x-ui.card class="border-l-4 border-l-primary-500">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <x-ui.icon name="calendar" class="w-5 h-5 text-primary-500" />
                            Shift Berikutnya
                        </h2>
                        @if($this->nextShift)
                            <div class="mt-2">
                                <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                                    {{ \Carbon\Carbon::parse($this->nextShift->date)->isoFormat('dddd, D MMMM') }}
                                </p>
                                <p class="text-gray-500 dark:text-gray-400 font-medium">
                                    Sesi {{ $this->nextShift->session }} • {{ $this->nextShift->schedule->name }}
                                </p>
                            </div>
                        @else
                            <p class="mt-2 text-gray-500 dark:text-gray-400">Belum ada jadwal shift mendatang.</p>
                        @endif
                    </div>

                    <div class="flex flex-col gap-2 w-full sm:w-auto">
                        <x-ui.button href="{{ route('admin.attendance.check-in-out') }}" size="lg" class="w-full justify-center shadow-lg shadow-primary-500/20">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                            </svg>
                            Check In / Out
                        </x-ui.button>
                        <p class="text-xs text-center text-gray-400">Pastikan berada di lokasi toko</p>
                    </div>
                </div>
            </x-ui.card>

            {{-- Live Shift Monitor --}}
            <x-ui.card title="Live Shift Monitor">
                @if($this->activeShifts->count() > 0)
                    <div class="space-y-4">
                        @foreach($this->activeShifts as $assignment)
                            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <x-ui.avatar :src="$assignment->user->photo_url" :name="$assignment->user->name" />
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-bold text-gray-900 dark:text-white truncate">
                                        {{ $assignment->user->name }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        @if($assignment->type === 'override')
                                            <span class="text-amber-600 font-medium">Override (Luar Jadwal)</span>
                                        @else
                                            Sesi {{ $assignment->session }} • {{ $assignment->schedule->name }}
                                        @endif
                                    </p>
                                </div>
                                <div class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <x-ui.icon name="clock" class="w-8 h-8 mx-auto mb-2 text-gray-300" />
                        <p>Tidak ada shift aktif saat ini</p>
                    </div>
                @endif
            </x-ui.card>

            {{-- Full Weekly Schedule (Jadwal Minggu Ini Semua User) --}}
            <x-ui.card title="Jadwal Operasional Minggu Ini">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left border-collapse">
                        <thead class="bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-3 font-medium border-b dark:border-gray-700">Hari / Tanggal</th>
                                <th class="px-4 py-3 font-medium border-b dark:border-gray-700">Petugas Shift</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($this->fullWeeklySchedule as $date => $assignments)
                                @php
                                    $isToday = $date === now()->format('Y-m-d');
                                @endphp
                                <tr class="{{ $isToday ? 'bg-primary-50/50 dark:bg-primary-900/10' : 'bg-white dark:bg-gray-800' }}">
                                    <td class="px-4 py-3 align-top w-1/4">
                                        <div class="flex flex-col">
                                            <span class="font-bold {{ $isToday ? 'text-primary-600 dark:text-primary-400' : 'text-gray-900 dark:text-white' }}">
                                                {{ \Carbon\Carbon::parse($date)->isoFormat('dddd') }}
                                            </span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ \Carbon\Carbon::parse($date)->isoFormat('D MMMM Y') }}
                                            </span>
                                            @if($isToday)
                                                <span class="mt-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200 w-fit">
                                                    Hari Ini
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            @foreach($assignments as $assignment)
                                                <div class="flex items-center gap-3 p-2 rounded-lg border {{ $assignment->user_id === auth()->id() ? 'border-primary-200 bg-primary-50 dark:border-primary-800 dark:bg-primary-900/20' : 'border-gray-100 bg-gray-50 dark:border-gray-700 dark:bg-gray-700/30' }}">
                                                    <x-ui.avatar :src="$assignment->user->photo_url" :name="$assignment->user->name" class="w-8 h-8" />
                                                    <div class="min-w-0">
                                                        <p class="text-sm font-medium {{ $assignment->user_id === auth()->id() ? 'text-primary-900 dark:text-primary-100' : 'text-gray-900 dark:text-white' }} truncate">
                                                            {{ $assignment->user->name }}
                                                        </p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                                            Sesi {{ $assignment->session }} • {{ $assignment->schedule->name }}
                                                        </p>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            
                            @if($this->fullWeeklySchedule->isEmpty())
                                <tr>
                                    <td colspan="2" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                        Tidak ada jadwal operasional minggu ini.
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </x-ui.card>

            {{-- Notifications (Bottom) --}}
            @if($this->userStats['notificationCount'] > 0)
                <x-ui.card class="bg-blue-50 dark:bg-blue-900/20 border-blue-100 dark:border-blue-800">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-bold text-blue-900 dark:text-blue-100 flex items-center gap-2">
                            <x-ui.icon name="bell" class="w-5 h-5" />
                            Notifikasi Terbaru
                        </h3>
                        <span class="px-2 py-0.5 rounded-full bg-blue-200 text-blue-800 text-xs font-bold">
                            {{ $this->userStats['notificationCount'] }}
                        </span>
                    </div>
                    <div class="space-y-2">
                        @foreach($this->userStats['notifications'] as $notif)
                            <div class="p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-blue-100 dark:border-gray-700">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $notif->title }}</p>
                                <p class="text-xs text-gray-600 dark:text-gray-300 mt-1">{{ $notif->message }}</p>
                                <p class="text-[10px] text-gray-400 mt-2 text-right">{{ $notif->created_at->diffForHumans() }}</p>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-3 text-center">
                        <a href="{{ route('admin.notifications.index') }}" class="text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400">
                            Lihat Semua Notifikasi
                        </a>
                    </div>
                </x-ui.card>
            @endif


        </div>
    </div>
</div>
