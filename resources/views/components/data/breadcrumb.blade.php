@props([
    'items' => [],
])

<nav {{ $attributes->merge(['class' => 'flex']) }} aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 md:space-x-2">
        @foreach($items as $index => $item)
            <li class="inline-flex items-center">
                @if($index > 0)
                    <x-ui.icon name="chevron-right" class="w-4 h-4 text-gray-400 mx-1 md:mx-2" />
                @endif
                
                @if(isset($item['url']) && $item['url'])
                    <a 
                        href="{{ $item['url'] }}" 
                        class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-blue-600 transition-colors duration-200"
                    >
                        @if(isset($item['icon']))
                            <x-ui.icon :name="$item['icon']" class="w-4 h-4 mr-1.5" />
                        @endif
                        {{ $item['label'] }}
                    </a>
                @else
                    <span class="inline-flex items-center text-sm font-medium text-gray-900">
                        @if(isset($item['icon']))
                            <x-ui.icon :name="$item['icon']" class="w-4 h-4 mr-1.5" />
                        @endif
                        {{ $item['label'] }}
                    </span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
