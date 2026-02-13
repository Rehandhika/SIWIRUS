@props([
    'variant' => 'default',     // default, success, danger, warning, info, primary
    'size' => 'md',             // sm, md
    'dot' => false,             // Show status dot
    'removable' => false,       // Show remove button
    'icon' => null,             // Optional icon (heroicon name)
    'onRemove' => null,         // Wire click action for remove button
])

@php
// Variant styling with dark mode support
$variants = [
    'default' => 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700',
    'primary' => 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-700',
    'success' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-700',
    'danger' => 'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-700',
    'warning' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-700',
    'info' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-700',
];

// Dot color mapping
$dotColors = [
    'default' => 'bg-gray-400 dark:bg-gray-500',
    'primary' => 'bg-blue-400 dark:bg-blue-500',
    'success' => 'bg-emerald-400 dark:bg-emerald-500',
    'danger' => 'bg-red-400 dark:bg-red-500',
    'warning' => 'bg-amber-400 dark:bg-amber-500',
    'info' => 'bg-blue-400 dark:bg-blue-500',
];

// Size configurations
$sizes = [
    'sm' => 'px-2 py-0.5 text-xs gap-1',
    'md' => 'px-2.5 py-1 text-sm gap-1.5',
];

// Icon size mapping
$iconSizes = [
    'sm' => 'w-3 h-3',
    'md' => 'w-4 h-4',
];

// Dot size mapping
$dotSizes = [
    'sm' => 'w-1.5 h-1.5',
    'md' => 'w-2 h-2',
];

$variantClass = $variants[$variant] ?? $variants['default'];
$sizeClass = $sizes[$size] ?? $sizes['md'];
$dotColor = $dotColors[$variant] ?? $dotColors['default'];
$iconSize = $iconSizes[$size] ?? $iconSizes['md'];
$dotSize = $dotSizes[$size] ?? $dotSizes['md'];
@endphp

<span {{ $attributes->merge([
    'class' => implode(' ', [
        'inline-flex items-center font-medium border rounded-full',
        $variantClass,
        $sizeClass,
    ])
]) }}>
    {{-- Status dot indicator --}}
    @if($dot)
        <span class="{{ $dotColor }} {{ $dotSize }} rounded-full"></span>
    @endif

    {{-- Optional icon --}}
    @if($icon)
        <x-ui.icon :name="$icon" class="{{ $iconSize }}" />
    @endif

    {{-- Badge content --}}
    <span>{{ $slot }}</span>

    {{-- Remove button --}}
    @if($removable)
        <button 
            type="button"
            @if($onRemove)
                wire:click="{{ $onRemove }}"
            @endif
            class="ml-0.5 -mr-1 inline-flex items-center justify-center rounded-full hover:bg-black/10 dark:hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-current transition-colors"
            aria-label="Remove"
        >
            <x-ui.icon name="x-mark" class="{{ $iconSize }}" />
        </button>
    @endif
</span>
