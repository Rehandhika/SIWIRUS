@props([
    'name' => '',
    'index' => 0,
    'icon' => null,
    'badge' => null,
])

@php
$isButton = !isset($attributes['panel']);
@endphp

@if($isButton)
    {{-- Tab Button --}}
    <button
        type="button"
        @click="activeTab = {{ $index }}"
        :class="{
            'border-blue-500 text-blue-600': activeTab === {{ $index }},
            'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== {{ $index }}
        }"
        {{ $attributes->merge(['class' => 'group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 rounded-t-lg']) }}
        :aria-selected="activeTab === {{ $index }}"
        role="tab"
    >
        @if($icon)
            <span 
                class="mr-2 w-5 h-5"
                :class="{
                    'text-blue-500': activeTab === {{ $index }},
                    'text-gray-400 group-hover:text-gray-500': activeTab !== {{ $index }}
                }"
            >
                <x-ui.icon :name="$icon" class="w-5 h-5" />
            </span>
        @endif
        
        <span>{{ $name }}</span>
        
        @if($badge)
            <span 
                :class="{
                    'bg-blue-100 text-blue-600': activeTab === {{ $index }},
                    'bg-gray-100 text-gray-600': activeTab !== {{ $index }}
                }"
                class="ml-2 py-0.5 px-2 rounded-full text-xs font-medium transition-colors duration-200"
            >
                {{ $badge }}
            </span>
        @endif
    </button>
@else
    {{-- Tab Panel --}}
    <div
        x-show="activeTab === {{ $index }}"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform translate-y-1"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 transform translate-y-0"
        x-transition:leave-end="opacity-0 transform translate-y-1"
        role="tabpanel"
        {{ $attributes->merge(['class' => '']) }}
    >
        {{ $slot }}
    </div>
@endif
