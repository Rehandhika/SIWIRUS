<div>
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Generator Jadwal</h1>
                <p class="text-gray-600 mt-1">Generate jadwal mingguan otomatis berdasarkan ketersediaan</p>
            </div>
            <div class="flex items-center space-x-2">
                @if($generatedCount > 0)
                    <div class="bg-green-50 text-green-700 px-3 py-1 rounded-full text-sm font-medium">
                        <i class="fas fa-check-circle mr-1"></i>
                        {{ $generatedCount }} jadwal digenerate
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Week Selection -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-calendar-week mr-2 text-blue-600"></i>
            Pilih Minggu
        </h2>
        
        <div class="flex items-center space-x-4">
            <button 
                wire:click="$set('selectedWeekOffset', $selectedWeekOffset - 1)"
                class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors"
            >
                <i class="fas fa-chevron-left"></i>
            </button>
            
            <div class="flex-1 text-center">
                <select 
                    wire:model.live="selectedWeekOffset"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                    <option value="0">Minggu Ini</option>
                    <option value="1">Minggu Depan</option>
                    <option value="2">2 Minggu Depan</option>
                    <option value="3">3 Minggu Depan</option>
                    <option value="4">4 Minggu Depan</option>
                </select>
                <p class="text-sm text-gray-600 mt-1">{{ $weekRange }}</p>
            </div>
            
            <button 
                wire:click="$set('selectedWeekOffset', $selectedWeekOffset + 1)"
                class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors"
            >
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>

    <!-- Configuration -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-cog mr-2 text-blue-600"></i>
            Konfigurasi Generate
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Tanggal Mulai
                </label>
                <input 
                    type="date" 
                    wire:model.live="startDate"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                @error('startDate')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Tanggal Selesai
                </label>
                <input 
                    type="date" 
                    wire:model.live="endDate"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                @error('endDate')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
        
        <div class="mt-4">
            <label class="flex items-center cursor-pointer">
                <input 
                    type="checkbox" 
                    wire:model="autoAssign"
                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                >
                <span class="ml-2 text-sm text-gray-700">Auto-assign pengguna berdasarkan ketersediaan</span>
            </label>
        </div>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="fas fa-calendar text-blue-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">Template Aktif</p>
                    <p class="text-xl font-bold text-gray-900">{{ count($scheduleTemplates) }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-users text-green-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">Pengguna Tersedia</p>
                    <p class="text-xl font-bold text-green-600">{{ count($availableUsers) }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="fas fa-clock text-blue-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">Hari Diproses</p>
                    <p class="text-xl font-bold text-blue-900">{{ \Carbon\Carbon::parse($startDate)->diffInDays(\Carbon\Carbon::parse($endDate)) + 1 }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="p-3 bg-yellow-100 rounded-lg">
                    <i class="fas fa-chart-line text-yellow-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">Estimasi Jadwal</p>
                    <p class="text-xl font-bold text-yellow-900">{{ count($scheduleTemplates) * 7 }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Section -->
    @if($showPreview)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-eye mr-2 text-blue-600"></i>
                    Preview Jadwal
                </h2>
                <button 
                    wire:click="$set('showPreview', false)"
                    class="text-gray-400 hover:text-gray-600"
                >
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Preview Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-blue-50 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-calendar-check text-blue-600 text-xl mr-3"></i>
                        <div>
                            <p class="text-sm text-blue-600">Total Jadwal</p>
                            <p class="text-xl font-bold text-blue-900">{{ $previewStats['total_assignments'] }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-green-50 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-users text-green-600 text-xl mr-3"></i>
                        <div>
                            <p class="text-sm text-green-600">Pengguna Terlibat</p>
                            <p class="text-xl font-bold text-green-900">{{ $previewStats['unique_users'] }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-blue-50 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-chart-bar text-blue-600 text-xl mr-3"></i>
                        <div>
                            <p class="text-sm text-blue-600">Rata-rata/Pengguna</p>
                            <p class="text-xl font-bold text-blue-900">{{ $previewStats['assignments_per_user'] }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-yellow-50 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-percentage text-yellow-600 text-xl mr-3"></i>
                        <div>
                            <p class="text-sm text-yellow-600">Coverage Rate</p>
                            <p class="text-xl font-bold text-yellow-900">{{ $previewStats['coverage_rate'] }}%</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Preview Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tanggal
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Hari
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Sesi
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Waktu
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Pengguna
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Template
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($previewAssignments as $assignment)
                            @php
                                $user = collect($availableUsers)->where('id', $assignment['user_id'])->first();
                                $template = collect($scheduleTemplates)->where('id', $assignment['schedule_id'])->first();
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ \Carbon\Carbon::parse($assignment['date'])->format('d/m/Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ \Carbon\Carbon::parse($assignment['date'])->locale('id')->format('l') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    Sesi {{ $assignment['session'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $assignment['time_start'] }} - {{ $assignment['time_end'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if($user)
                                        {{ $user->name }}
                                        <span class="text-gray-500">({{ $user->nim }})</span>
                                    @else
                                        <span class="text-red-600">User tidak ditemukan</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if($template)
                                        {{ $template->name }}
                                    @else
                                        <span class="text-red-600">Template tidak ditemukan</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Actions -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                @if($generatedCount > 0)
                    <p class="text-sm text-green-600">
                        <i class="fas fa-check-circle mr-1"></i>
                        Jadwal berhasil digenerate untuk minggu ini
                    </p>
                @endif
            </div>
            
            <div class="flex items-center space-x-3">
                @if($showPreview)
                    <button 
                        wire:click="$set('showPreview', false)"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
                    >
                        <i class="fas fa-times mr-2"></i>
                        Batal Preview
                    </button>
                @else
                    <button 
                        wire:click="generatePreview"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove>
                            <i class="fas fa-eye mr-2"></i>
                            Preview Jadwal
                        </span>
                        <span wire:loading>
                            <i class="fas fa-spinner fa-spin mr-2"></i>
                            Loading...
                        </span>
                    </button>
                @endif
                
                @if($generatedCount > 0)
                    <button 
                        wire:click="clearGeneratedSchedules"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove>
                            <i class="fas fa-trash mr-2"></i>
                            Hapus Jadwal
                        </span>
                        <span wire:loading>
                            <i class="fas fa-spinner fa-spin mr-2"></i>
                            Menghapus...
                        </span>
                    </button>
                @endif
                
                <button 
                    wire:click="generateSchedule"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    wire:loading.attr="disabled"
                    {{ empty($scheduleTemplates) || empty($availableUsers) ? 'disabled' : '' }}
                >
                    <span wire:loading.remove>
                        <i class="fas fa-magic mr-2"></i>
                        {{ $generatedCount > 0 ? 'Regenerate' : 'Generate' }} Jadwal
                    </span>
                    <span wire:loading>
                        <i class="fas fa-spinner fa-spin mr-2"></i>
                        {{ $isGenerating ? 'Generating...' : 'Loading...' }}
                    </span>
                </button>
            </div>
        </div>
        
        <!-- Warnings -->
        @if(empty($scheduleTemplates))
            <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <p class="text-sm text-yellow-800">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    <strong>Perhatian:</strong> Tidak ada template jadwal yang aktif. 
                    <a href="#" class="text-blue-600 hover:text-blue-700 underline">Buat template jadwal</a> terlebih dahulu.
                </p>
            </div>
        @elseif(empty($availableUsers))
            <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <p class="text-sm text-yellow-800">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    <strong>Perhatian:</strong> Tidak ada pengguna dengan ketersediaan untuk minggu ini. 
                    Pastikan pengguna sudah mengisi ketersediaan mereka.
                </p>
            </div>
        @endif
    </div>
</div>
