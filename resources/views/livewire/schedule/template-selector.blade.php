<div>
    @if($show)
    {{-- Modal Overlay --}}
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            {{-- Background overlay --}}
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                 wire:click="closeModal"></div>

            {{-- Modal panel --}}
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-5xl sm:w-full">
                {{-- Header --}}
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-white" id="modal-title">
                                Pilih Template Jadwal
                            </h3>
                            <p class="text-sm text-blue-100 mt-1">
                                Gunakan template untuk mempercepat pembuatan jadwal
                            </p>
                        </div>
                        <button wire:click="closeModal" 
                                class="text-white hover:text-gray-200 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Search and Filter --}}
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <div class="flex items-center space-x-4">
                        {{-- Search --}}
                        <div class="flex-1 relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input type="text" 
                                   wire:model.live.debounce.300ms="search"
                                   class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Cari template...">
                        </div>
                        
                        {{-- Filter --}}
                        <div class="flex items-center space-x-2">
                            <button wire:click="$set('filterType', 'all')"
                                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $filterType === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                                Semua
                            </button>
                            <button wire:click="$set('filterType', 'my')"
                                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $filterType === 'my' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                                Saya
                            </button>
                            <button wire:click="$set('filterType', 'public')"
                                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $filterType === 'public' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                                Publik
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Content --}}
                <div class="flex h-[60vh]">
                    {{-- Template List --}}
                    <div class="w-1/2 border-r border-gray-200 overflow-y-auto">
                        @if(count($templates) > 0)
                            <div class="divide-y divide-gray-200">
                                @foreach($templates as $template)
                                    <div wire:click="previewTemplate({{ $template['id'] }})"
                                         class="p-4 hover:bg-gray-50 cursor-pointer transition-colors {{ $selectedTemplateId === $template['id'] ? 'bg-blue-50 border-l-4 border-blue-600' : '' }}">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <div class="flex items-center space-x-2">
                                                    <h4 class="text-sm font-semibold text-gray-900">
                                                        {{ $template['name'] }}
                                                    </h4>
                                                    @if($template['is_public'])
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                                                            </svg>
                                                            Publik
                                                        </span>
                                                    @endif
                                                </div>
                                                
                                                @if($template['description'])
                                                    <p class="text-xs text-gray-600 mt-1">
                                                        {{ $template['description'] }}
                                                    </p>
                                                @endif
                                                
                                                <div class="flex items-center space-x-4 mt-2 text-xs text-gray-500">
                                                    <span class="flex items-center">
                                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                        </svg>
                                                        {{ $template['creator']['name'] ?? 'Unknown' }}
                                                    </span>
                                                    <span class="flex items-center">
                                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                        </svg>
                                                        {{ $template['usage_count'] }} kali digunakan
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            {{-- Actions --}}
                                            @if($template['created_by'] === auth()->id())
                                                <button wire:click.stop="deleteTemplate({{ $template['id'] }})"
                                                        wire:confirm="Yakin ingin menghapus template ini?"
                                                        class="ml-2 p-1 text-red-600 hover:bg-red-50 rounded transition-colors"
                                                        title="Hapus template">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="flex items-center justify-center h-full">
                                <div class="text-center py-12">
                                    <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <p class="mt-4 text-sm text-gray-600">
                                        @if($search)
                                            Tidak ada template yang cocok dengan pencarian "{{ $search }}"
                                        @else
                                            Belum ada template tersedia
                                        @endif
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Template Preview --}}
                    <div class="w-1/2 overflow-y-auto bg-gray-50">
                        @if($previewTemplate)
                            <div class="p-6">
                                {{-- Template Info --}}
                                <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
                                    <h4 class="text-lg font-semibold text-gray-900 mb-2">
                                        {{ $previewTemplate['name'] }}
                                    </h4>
                                    @if($previewTemplate['description'])
                                        <p class="text-sm text-gray-600 mb-3">
                                            {{ $previewTemplate['description'] }}
                                        </p>
                                    @endif
                                    
                                    @php
                                        $stats = $this->getPatternStats($previewTemplate['pattern']);
                                    @endphp
                                    
                                    <div class="grid grid-cols-3 gap-3 mt-3">
                                        <div class="text-center p-2 bg-blue-50 rounded">
                                            <p class="text-xs text-blue-600 font-medium">Assignments</p>
                                            <p class="text-lg font-bold text-blue-900">{{ $stats['total_assignments'] }}</p>
                                        </div>
                                        <div class="text-center p-2 bg-green-50 rounded">
                                            <p class="text-xs text-green-600 font-medium">Coverage</p>
                                            <p class="text-lg font-bold text-green-900">{{ $stats['coverage'] }}%</p>
                                        </div>
                                        <div class="text-center p-2 bg-blue-50 rounded">
                                            <p class="text-xs text-blue-600 font-medium">Users</p>
                                            <p class="text-lg font-bold text-blue-900">{{ $stats['unique_users'] }}</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Pattern Preview --}}
                                <div class="bg-white rounded-lg shadow-sm p-4">
                                    <h5 class="text-sm font-semibold text-gray-900 mb-3">Pattern Preview</h5>
                                    
                                    @php
                                        $groupedPattern = collect($previewTemplate['pattern'])->groupBy('day');
                                    @endphp
                                    
                                    <div class="space-y-3">
                                        @foreach(['monday', 'tuesday', 'wednesday', 'thursday'] as $day)
                                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                                <div class="bg-gray-100 px-3 py-2 border-b border-gray-200">
                                                    <p class="text-xs font-semibold text-gray-700">
                                                        {{ $this->getDayName($day) }}
                                                    </p>
                                                </div>
                                                <div class="divide-y divide-gray-200">
                                                    @for($session = 1; $session <= 3; $session++)
                                                        @php
                                                            $assignment = collect($groupedPattern->get($day, []))->firstWhere('session', $session);
                                                        @endphp
                                                        <div class="px-3 py-2 flex items-center justify-between">
                                                            <span class="text-xs text-gray-500">
                                                                Sesi {{ $session }} ({{ $this->getSessionTime($session) }})
                                                            </span>
                                                            @if($assignment)
                                                                <div class="flex items-center space-x-2">
                                                                    <span class="text-xs font-medium text-gray-900">
                                                                        {{ $assignment['user_name'] }}
                                                                    </span>
                                                                    @if($assignment['user_status'] !== 'active')
                                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                                                            Tidak Aktif
                                                                        </span>
                                                                    @endif
                                                                </div>
                                                            @else
                                                                <span class="text-xs text-gray-400 italic">Tidak ada assignment</span>
                                                            @endif
                                                        </div>
                                                    @endfor
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Select Button --}}
                                <button wire:click="selectTemplate({{ $previewTemplate['id'] }})"
                                        class="w-full mt-4 btn btn-primary">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Gunakan Template Ini
                                </button>
                            </div>
                        @else
                            <div class="flex items-center justify-center h-full">
                                <div class="text-center py-12">
                                    <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <p class="mt-4 text-sm text-gray-600">
                                        Pilih template untuk melihat preview
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Footer --}}
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <p class="text-xs text-gray-600">
                            Total: {{ count($templates) }} template
                        </p>
                        <button wire:click="closeModal" 
                                class="btn btn-secondary">
                            Batal
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
