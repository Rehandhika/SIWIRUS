@props([
    'item' => [],
    'accessible' => true,
])

@php
// Navigation link base classes
$baseClasses = 'flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2';
$activeClasses = 'bg-blue-50 text-blue-700';
$inactiveClasses = 'text-gray-700 hover:bg-gray-100 hover:text-gray-900';
$lockedClasses = 'text-gray-400 cursor-not-allowed opacity-60';

// Determine if current route matches this menu item
$itemRoute = $item['route'] ?? null;
$activeRoutes = $item['active_routes'] ?? null;
$isActive = false;

if ($activeRoutes && is_array($activeRoutes)) {
    // Use explicit active_routes if defined
    $isActive = request()->routeIs($activeRoutes);
} elseif ($itemRoute) {
    // Check exact match
    $isActive = request()->routeIs($itemRoute);
}
@endphp

@if($accessible)
    {{-- Accessible Menu Item - Normal Link --}}
    <a href="{{ isset($item['route']) ? route($item['route']) : '#' }}" 
       class="{{ $baseClasses }} {{ $isActive ? $activeClasses : $inactiveClasses }}"
       aria-current="{{ $isActive ? 'page' : 'false' }}">
        @if(isset($item['icon']))
            <x-ui.icon :name="$item['icon']" class="w-5 h-5 mr-3 flex-shrink-0" />
        @endif
        <span>{{ $item['label'] ?? '' }}</span>
    </a>
@else
    {{-- Locked Menu Item - Non-clickable with lock icon --}}
    <div class="{{ $baseClasses }} {{ $lockedClasses }}"
         role="button"
         aria-disabled="true"
         aria-label="{{ $item['label'] ?? '' }} - Akses terkunci"
         tabindex="0"
         title="Anda tidak memiliki akses ke menu ini"
         x-data
         @click.prevent="$dispatch('show-access-denied', { menu: '{{ $item['label'] ?? '' }}' })"
         @keydown.enter.prevent="$dispatch('show-access-denied', { menu: '{{ $item['label'] ?? '' }}' })"
         @keydown.space.prevent="$dispatch('show-access-denied', { menu: '{{ $item['label'] ?? '' }}' })">
        @if(isset($item['icon']))
            <x-ui.icon :name="$item['icon']" class="w-5 h-5 mr-3 flex-shrink-0" />
        @endif
        <span class="flex-1">{{ $item['label'] ?? '' }}</span>
        <x-ui.icon name="lock-closed" class="w-4 h-4 ml-2 text-gray-400" aria-label="Menu terkunci" />
    </div>
@endif
