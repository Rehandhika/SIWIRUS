@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'loading' => false,
    'disabled' => false,
    'icon' => null,
    'iconPosition' => 'left',
    'href' => null,
])

@php
// Variant styling with consistent dark mode support
$variants = [
    'primary' => 'bg-blue-600 hover:bg-blue-700 text-white focus:ring-blue-500 dark:bg-blue-500 dark:hover:bg-blue-600',
    'secondary' => 'bg-gray-600 hover:bg-gray-700 text-white focus:ring-gray-500 dark:bg-gray-500 dark:hover:bg-gray-600',
    'success' => 'bg-emerald-500 hover:bg-emerald-700 text-white focus:ring-emerald-500 dark:bg-emerald-600 dark:hover:bg-emerald-700',
    'danger' => 'bg-red-500 hover:bg-red-700 text-white focus:ring-red-500 dark:bg-red-600 dark:hover:bg-red-700',
    'warning' => 'bg-amber-500 hover:bg-amber-600 text-white focus:ring-amber-500 dark:bg-amber-600 dark:hover:bg-amber-700',
    'info' => 'bg-blue-500 hover:bg-blue-700 text-white focus:ring-blue-500 dark:bg-blue-600 dark:hover:bg-blue-700',
    'white' => 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 focus:ring-blue-500 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700',
    'outline' => 'bg-transparent border-2 border-blue-600 text-blue-600 hover:bg-blue-50 focus:ring-blue-500 dark:border-blue-400 dark:text-blue-400 dark:hover:bg-blue-900/30',
    'ghost' => 'bg-transparent text-gray-700 hover:bg-gray-100 focus:ring-gray-500 dark:text-gray-300 dark:hover:bg-gray-800',
];

// Size configurations
$sizes = [
    'sm' => 'px-3 py-1.5 text-xs',
    'md' => 'px-4 py-2 text-sm',
    'lg' => 'px-6 py-3 text-base',
];

// Icon size mapping based on button size
$iconSizes = [
    'sm' => 'w-4 h-4',
    'md' => 'w-5 h-5',
    'lg' => 'w-6 h-6',
];

// Base classes with consistent rounded-lg
$baseClasses = 'inline-flex items-center justify-center font-semibold rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';

$isDisabled = $loading || $disabled;
$iconSize = $iconSizes[$size] ?? $iconSizes['md'];
@endphp

@if($href && !$isDisabled)
<a
    href="{{ $href }}"
    {{ $attributes->merge(['class' => implode(' ', [$baseClasses, $variants[$variant], $sizes[$size]])]) }}
>
    @if($loading)
        <x-ui.spinner class="{{ $iconPosition === 'right' ? 'ml-2' : 'mr-2' }}" :size="$size === 'lg' ? 'md' : 'sm'" color="white" />
    @elseif($icon && $iconPosition === 'left')
        <x-ui.icon :name="$icon" class="mr-2 {{ $iconSize }}" />
    @endif
    
    {{ $slot }}
    
    @if($icon && $iconPosition === 'right' && !$loading)
        <x-ui.icon :name="$icon" class="ml-2 {{ $iconSize }}" />
    @endif
</a>
@else
<button
    type="{{ $type }}"
    {{ $attributes->merge(['class' => implode(' ', [$baseClasses, $variants[$variant], $sizes[$size]])]) }}
    @if($isDisabled) disabled @endif
>
    @if($loading)
        <x-ui.spinner class="{{ $iconPosition === 'right' ? 'ml-2' : 'mr-2' }}" :size="$size === 'lg' ? 'md' : 'sm'" color="white" />
    @elseif($icon && $iconPosition === 'left')
        <x-ui.icon :name="$icon" class="mr-2 {{ $iconSize }}" />
    @endif
    
    {{ $slot }}
    
    @if($icon && $iconPosition === 'right' && !$loading)
        <x-ui.icon :name="$icon" class="ml-2 {{ $iconSize }}" />
    @endif
</button>
@endif
