@props([
    'label' => null,
    'wire' => null,
    'options' => [],
    'placeholder' => 'Pilih...',
    'searchable' => false,
    'name' => null,
    'required' => false,
])

<div class="space-y-1">
    @if($label)
        <label @if($name) for="{{ $name }}" @endif class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $label }}
            @if($required)
                <span class="text-danger-500">*</span>
            @endif
        </label>
    @endif

    <div x-data="{ 
        open: false, 
        selected: @if($wire) $wire.entangle('{{ $wire }}') @else '' @endif,
        search: '',
        options: {{ json_encode($options) }},
        get filteredOptions() {
            if (!this.search) return this.options;
            return this.options.filter(o => o.label.toLowerCase().includes(this.search.toLowerCase()));
        },
        get selectedLabel() {
            const found = this.options.find(o => String(o.value) === String(this.selected));
            return found ? found.label : '{{ $placeholder }}';
        },
        selectOption(value) {
            this.selected = value;
            this.open = false;
            this.search = '';
        }
    }" 
    x-init="$watch('open', value => { if (!value) search = '' })"
    {{ $attributes->merge(['class' => 'relative']) }}>
        
        {{-- Trigger Button --}}
        <button @click="open = !open" 
            @keydown.escape.window="open = false"
            type="button"
            class="w-full flex items-center justify-between gap-2 px-3 py-2 text-sm text-left bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:border-gray-400 dark:hover:border-gray-500 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
            <span x-text="selectedLabel" class="truncate text-gray-900 dark:text-white"></span>
            <svg class="w-4 h-4 text-gray-400 shrink-0 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        
        {{-- Dropdown Panel --}}
        <div x-show="open" 
            @click.away="open = false"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg overflow-hidden"
            style="display: none;">
            
            @if($searchable)
            {{-- Search Input --}}
            <div class="p-2 border-b border-gray-200 dark:border-gray-600">
                <input x-model="search" 
                    type="text" 
                    placeholder="Cari..."
                    class="w-full px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-1 focus:ring-primary-500 dark:bg-gray-800 dark:text-white"
                    @click.stop>
            </div>
            @endif
            
            {{-- Options List --}}
            <div class="max-h-48 overflow-y-auto">
                <template x-for="option in filteredOptions" :key="option.value">
                    <button @click="selectOption(option.value)" 
                        type="button"
                        class="w-full px-3 py-2 text-sm text-left hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors flex items-center justify-between"
                        :class="{ 'bg-primary-50 dark:bg-primary-900/30': String(selected) === String(option.value) }">
                        <span x-text="option.label" :class="{ 'text-primary-700 dark:text-primary-300 font-medium': String(selected) === String(option.value), 'text-gray-700 dark:text-gray-200': String(selected) !== String(option.value) }"></span>
                        <svg x-show="String(selected) === String(option.value)" class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </button>
                </template>
                <div x-show="filteredOptions.length === 0" class="px-3 py-2 text-sm text-gray-500 text-center">
                    Tidak ada hasil
                </div>
            </div>
        </div>
    </div>
</div>
