<div>
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Template Jadwal</h1>
                <p class="text-gray-600 mt-1">Kelola template jadwal untuk generate penugasan mingguan</p>
            </div>
            <button 
                wire:click="createTemplate"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
            >
                <i class="fas fa-plus mr-2"></i>
                Buat Template
            </button>
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
                    <p class="text-sm text-gray-600">Total Template</p>
                    <p class="text-xl font-bold text-gray-900">{{ $stats['total'] }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-check-circle text-green-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">Aktif</p>
                    <p class="text-xl font-bold text-green-600">{{ $stats['active'] }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="p-3 bg-gray-100 rounded-lg">
                    <i class="fas fa-pause-circle text-gray-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">Non-aktif</p>
                    <p class="text-xl font-bold text-gray-600">{{ $stats['inactive'] }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="fas fa-layer-group text-blue-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">Sesi</p>
                    <p class="text-xl font-bold text-blue-600">{{ $stats['by_session']->count() }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Templates by Day -->
    <div class="space-y-6">
        @foreach($templatesByDay as $day => $dayTemplates)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900">
                            <i class="fas fa-calendar-day text-blue-600 mr-2"></i>
                            {{ $this->getDayName($day) }}
                        </h2>
                        <span class="text-sm text-gray-500">
                            {{ $dayTemplates->count() }} template
                        </span>
                    </div>
                </div>
                
                <div class="p-6">
                    @if($dayTemplates->isEmpty())
                        <div class="text-center py-8 bg-gray-50 rounded-lg">
                            <i class="fas fa-calendar-times text-4xl text-gray-400 mb-3"></i>
                            <p class="text-gray-600">Belum ada template untuk {{ $this->getDayName($day) }}</p>
                            <button 
                                wire:click="createTemplate"
                                class="mt-2 text-sm text-blue-600 hover:text-blue-700"
                            >
                                <i class="fas fa-plus mr-1"></i>
                                Buat Template Baru
                            </button>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach($dayTemplates as $template)
                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center mb-2">
                                                <h3 class="font-medium text-gray-900">{{ $template->name }}</h3>
                                                @if($template->is_active)
                                                    <span class="ml-2 px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">
                                                        Aktif
                                                    </span>
                                                @else
                                                    <span class="ml-2 px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded-full">
                                                        Non-aktif
                                                    </span>
                                                @endif
                                            </div>
                                            
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                                <div class="flex items-center text-gray-600">
                                                    <i class="fas fa-layer-group mr-2 text-gray-400"></i>
                                                    {{ $this->getSessionName($template->session) }}
                                                </div>
                                                <div class="flex items-center text-gray-600">
                                                    <i class="fas fa-clock mr-2 text-gray-400"></i>
                                                    {{ $template->time_start }} - {{ $template->time_end }}
                                                </div>
                                                <div class="flex items-center text-gray-600">
                                                    <i class="fas fa-hourglass-half mr-2 text-gray-400"></i>
                                                    {{ \Carbon\Carbon::parse($template->time_start)->diffInMinutes(\Carbon\Carbon::parse($template->time_end)) }} menit
                                                </div>
                                            </div>
                                            
                                            @if($template->description)
                                                <p class="mt-2 text-sm text-gray-500">{{ $template->description }}</p>
                                            @endif
                                            
                                            <!-- Usage Info -->
                                            @php
                                                $assignmentCount = \App\Models\ScheduleAssignment::where('schedule_id', $template->id)->count();
                                            @endphp
                                            @if($assignmentCount > 0)
                                                <div class="mt-3 p-2 bg-blue-50 rounded text-xs text-blue-700">
                                                    <i class="fas fa-info-circle mr-1"></i>
                                                    Digunakan dalam {{ $assignmentCount }} penugasan
                                                </div>
                                            @endif
                                        </div>
                                        
                                        <div class="flex items-center space-x-2 ml-4">
                                            <button 
                                                wire:click="editTemplate({{ $template->id }})"
                                                class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                                title="Edit"
                                            >
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button 
                                                wire:click="duplicateTemplate({{ $template->id }})"
                                                class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition-colors"
                                                title="Duplikat"
                                            >
                                                <i class="fas fa-copy"></i>
                                            </button>
                                            <button 
                                                wire:click="toggleStatus({{ $template->id }})"
                                                class="p-2 {{ $template->is_active ? 'text-yellow-600 hover:bg-yellow-50' : 'text-green-600 hover:bg-green-50' }} rounded-lg transition-colors"
                                                title="{{ $template->is_active ? 'Non-aktifkan' : 'Aktifkan' }}"
                                            >
                                                <i class="fas {{ $template->is_active ? 'fa-pause' : 'fa-play' }}"></i>
                                            </button>
                                            <button 
                                                wire:click="deleteTemplate({{ $template->id }})"
                                                class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                                title="Hapus"
                                                wire:loading.attr="disabled"
                                            >
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <!-- Empty State -->
    @if($templates->isEmpty())
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
            <i class="fas fa-calendar-alt text-6xl text-gray-400 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Belum Ada Template Jadwal</h3>
            <p class="text-gray-600 mb-6">Buat template jadwal untuk mempermudah generate penugasan mingguan</p>
            <button 
                wire:click="createTemplate"
                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
            >
                <i class="fas fa-plus mr-2"></i>
                Buat Template Pertama
            </button>
        </div>
    @endif

    <!-- Modal -->
    @if($showModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-calendar-plus text-blue-600 mr-2"></i>
                        {{ $isEditing ? 'Edit Template' : 'Buat Template Baru' }}
                    </h3>
                    <button 
                        wire:click="closeModal"
                        class="text-gray-400 hover:text-gray-600"
                    >
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form wire:submit="saveTemplate">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Nama Template <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                wire:model="name"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="contoh: Shift Pagi Senin"
                            >
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Hari <span class="text-red-500">*</span>
                                </label>
                                <select 
                                    wire:model="day"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                                    <option value="">Pilih Hari</option>
                                    @foreach(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $dayOption)
                                        <option value="{{ $dayOption }}">{{ $this->getDayName($dayOption) }}</option>
                                    @endforeach
                                </select>
                                @error('day')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Sesi <span class="text-red-500">*</span>
                                </label>
                                <select 
                                    wire:model="session"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                                    <option value="1">Sesi 1 (Pagi)</option>
                                    <option value="2">Sesi 2 (Siang)</option>
                                    <option value="3">Sesi 3 (Sore)</option>
                                </select>
                                @error('session')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Waktu Mulai <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="time" 
                                    wire:model="timeStart"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                                @error('timeStart')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Waktu Selesai <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="time" 
                                    wire:model="timeEnd"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                                @error('timeEnd')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Deskripsi
                            </label>
                            <textarea 
                                wire:model="description"
                                rows="2"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Deskripsi singkat template (opsional)"
                            ></textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label class="flex items-center cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    wire:model="isActive"
                                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                >
                                <span class="ml-2 text-sm text-gray-700">Template aktif</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button 
                            type="button"
                            wire:click="closeModal"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                        >
                            Batal
                        </button>
                        <button 
                            type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove>
                                {{ $isEditing ? 'Perbarui' : 'Simpan' }}
                            </span>
                            <span wire:loading>
                                <i class="fas fa-spinner fa-spin mr-1"></i>
                                Menyimpan...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
