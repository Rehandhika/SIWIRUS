<div>
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Kalender Jadwal</h1>
                <p class="text-gray-600 mt-1">Lihat dan kelola jadwal penugasan mingguan</p>
            </div>
            <div class="flex items-center space-x-3">
                <button 
                    wire:click="exportCalendar"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
                >
                    <i class="fas fa-download mr-2"></i>
                    Export
                </button>
                <button 
                    wire:click="goToToday"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                >
                    <i class="fas fa-calendar-day mr-2"></i>
                    Hari Ini
                </button>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-filter mr-2 text-blue-600"></i>
                Filter & Pencarian
            </h2>
            <button 
                wire:click="resetFilters"
                class="text-sm text-gray-600 hover:text-gray-900 transition-colors"
            >
                <i class="fas fa-redo mr-1"></i>
                Reset Filter
            </button>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Pengguna</label>
                <select 
                    wire:model.live="filterUser"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                >
                    <option value="">Semua Pengguna</option>
                    @foreach($availableUsers as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sesi</label>
                <select 
                    wire:model.live="filterSession"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                >
                    @foreach($sessionOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Pencarian</label>
                <div class="relative">
                    <input 
                        type="text" 
                        wire:model.live="search"
                        placeholder="Cari berdasarkan nama atau NIM..."
                        class="w-full px-3 py-2 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                    >
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="fas fa-calendar-check text-blue-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">Total Penugasan</p>
                    <p class="text-xl font-bold text-gray-900">{{ $monthStats['total_assignments'] }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-users text-green-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">Pengguna Terlibat</p>
                    <p class="text-xl font-bold text-green-600">{{ $monthStats['unique_users'] }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="fas fa-clock text-blue-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">Total Jam</p>
                    <p class="text-xl font-bold text-blue-900">{{ $monthStats['total_hours'] }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="p-3 bg-yellow-100 rounded-lg">
                    <i class="fas fa-chart-line text-yellow-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">Hari Aktif</p>
                    <p class="text-xl font-bold text-yellow-900">{{ $monthStats['coverage_days'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendar Navigation -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between">
            <button 
                wire:click="previousMonth"
                class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors"
            >
                <i class="fas fa-chevron-left"></i>
            </button>
            
            <h2 class="text-xl font-bold text-gray-900">{{ $monthName }}</h2>
            
            <button 
                wire:click="nextMonth"
                class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors"
            >
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>

    <!-- Calendar Grid -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <!-- Week Days Header -->
        <div class="grid grid-cols-7 gap-px bg-gray-200 rounded-t-lg overflow-hidden">
            @foreach(['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $day)
                <div class="bg-gray-50 p-3 text-center text-sm font-medium text-gray-700">
                    {{ $day }}
                </div>
            @endforeach
        </div>
        
        <!-- Calendar Days -->
        <div class="grid grid-cols-7 gap-px bg-gray-200 rounded-b-lg overflow-hidden">
            @foreach($calendarDays as $day)
                @php
                    $bgClass = 'bg-white';
                    $textClass = 'text-gray-900';
                    
                    if (!$day['is_current_month']) {
                        $bgClass = 'bg-gray-50';
                        $textClass = 'text-gray-400';
                    } elseif ($day['is_today']) {
                        $bgClass = 'bg-blue-50';
                    } elseif ($day['is_weekend']) {
                        $bgClass = 'bg-red-50';
                    }
                @endphp
                
                <div class="{{ $bgClass }} {{ $textClass }} p-2 min-h-[100px] cursor-pointer hover:bg-gray-100 transition-colors"
                     wire:click="selectDate('{{ $day['date'] }}')">
                    
                    <!-- Day Number -->
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm font-medium {{ $day['is_today'] ? 'text-blue-600 font-bold' : '' }}">
                            {{ $day['day'] }}
                        </span>
                        @if($day['assignments']->count() > 0)
                            <span class="text-xs bg-blue-100 text-blue-600 px-1.5 py-0.5 rounded-full font-medium">
                                {{ $day['assignments']->count() }}
                            </span>
                        @endif
                    </div>
                    
                    <!-- Assignments -->
                    <div class="space-y-1">
                        @foreach($day['assignments']->take(3) as $assignment)
                            <div class="text-xs p-1 rounded bg-blue-100 text-blue-800 truncate hover:bg-blue-200 transition-colors">
                                <div class="font-medium">{{ $assignment->user->name }}</div>
                                <div class="text-xs opacity-75">{{ $assignment->time_start }}</div>
                            </div>
                        @endforeach
                        
                        @if($day['assignments']->count() > 3)
                            <div class="text-xs text-gray-500 text-center font-medium">
                                +{{ $day['assignments']->count() - 3 }} lagi
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Selected Date Details -->
    @if($showDetails)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-calendar-day text-blue-600 mr-2"></i>
                    Detail Jadwal - {{ \Carbon\Carbon::parse($selectedDate)->locale('id')->format('l, d F Y') }}
                </h3>
                <button 
                    wire:click="closeDetails"
                    class="text-gray-400 hover:text-gray-600 transition-colors p-2 hover:bg-gray-100 rounded-lg"
                >
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            @if($selectedDateAssignments->isNotEmpty())
                <div class="space-y-3">
                    @foreach($selectedDateAssignments as $assignment)
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md hover:border-blue-300 transition-all">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center mb-3">
                                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                                            <i class="fas fa-user text-blue-600"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-gray-900">{{ $assignment->user->name }}</h4>
                                            <p class="text-sm text-gray-500">{{ $assignment->user->nim }}</p>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                                        <div class="flex items-center text-gray-600">
                                            <i class="fas fa-layer-group mr-2 text-gray-400 w-4"></i>
                                            <span>Sesi {{ $assignment->session }}</span>
                                        </div>
                                        <div class="flex items-center text-gray-600">
                                            <i class="fas fa-clock mr-2 text-gray-400 w-4"></i>
                                            <span>{{ $assignment->time_start }} - {{ $assignment->time_end }}</span>
                                        </div>
                                        <div class="flex items-center text-gray-600">
                                            <i class="fas fa-hourglass-half mr-2 text-gray-400 w-4"></i>
                                            <span>{{ \Carbon\Carbon::parse($assignment->time_start)->diffInMinutes(\Carbon\Carbon::parse($assignment->time_end)) }} menit</span>
                                        </div>
                                    </div>
                                    
                                    @if($assignment->schedule)
                                        <div class="mt-3 text-sm text-gray-600 bg-gray-50 rounded-lg p-2">
                                            <i class="fas fa-info-circle mr-1 text-blue-500"></i>
                                            <span class="font-medium">Template:</span> {{ $assignment->schedule->name }}
                                        </div>
                                    @endif
                                </div>
                                
                                <div class="flex items-center space-x-2 ml-4">
                                    <button 
                                        wire:click="viewAssignment({{ $assignment->id }})"
                                        class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                        title="Lihat Detail"
                                    >
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 bg-gray-50 rounded-lg">
                    <i class="fas fa-calendar-times text-4xl text-gray-400 mb-3"></i>
                    <p class="text-gray-600 font-medium">Tidak ada jadwal untuk tanggal ini</p>
                    <p class="text-sm text-gray-500 mt-1">Coba filter lain atau pilih tanggal berbeda</p>
                </div>
            @endif
        </div>
    @endif

    <!-- Assignment Detail Modal -->
    @if($selectedAssignment)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 transition-opacity duration-300">
            <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4 transform transition-all duration-300 scale-100">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Detail Penugasan</h3>
                    <button 
                        wire:click="$set('selectedAssignment', null)"
                        class="text-gray-400 hover:text-gray-600 transition-colors p-2 hover:bg-gray-100 rounded-lg"
                    >
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="space-y-4">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-user text-blue-600 text-lg"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">{{ $selectedAssignment->user->name }}</p>
                            <p class="text-sm text-gray-500">{{ $selectedAssignment->user->nim }}</p>
                        </div>
                    </div>
                    
                    <div class="border-t border-gray-200 pt-4">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-gray-600 mb-1">Tanggal</p>
                                <p class="font-medium text-gray-900">
                                    {{ \Carbon\Carbon::parse($selectedAssignment->date)->locale('id')->format('d F Y') }}
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-600 mb-1">Hari</p>
                                <p class="font-medium text-gray-900">
                                    {{ \Carbon\Carbon::parse($selectedAssignment->date)->locale('id')->format('l') }}
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-600 mb-1">Sesi</p>
                                <p class="font-medium text-gray-900">Sesi {{ $selectedAssignment->session }}</p>
                            </div>
                            <div>
                                <p class="text-gray-600 mb-1">Durasi</p>
                                <p class="font-medium text-gray-900">
                                    {{ $selectedAssignment->time_start }} - {{ $selectedAssignment->time_end }}
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    @if($selectedAssignment->schedule)
                        <div class="border-t border-gray-200 pt-4">
                            <p class="text-sm text-gray-600 mb-2">Template Jadwal</p>
                            <div class="bg-blue-50 rounded-lg p-3">
                                <p class="font-medium text-gray-900">{{ $selectedAssignment->schedule->name }}</p>
                                @if($selectedAssignment->schedule->description)
                                    <p class="text-sm text-gray-600 mt-1">{{ $selectedAssignment->schedule->description }}</p>
                                @endif
                            </div>
                        </div>
                    @endif
                    
                    <div class="border-t border-gray-200 pt-4">
                        <p class="text-sm text-gray-600 mb-2">Status</p>
                        <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                            <i class="fas fa-check-circle mr-1"></i>
                            Terjadwal
                        </span>
                    </div>
                </div>
                
                <div class="flex justify-end mt-6 pt-4 border-t border-gray-200">
                    <button 
                        wire:click="$set('selectedAssignment', null)"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
