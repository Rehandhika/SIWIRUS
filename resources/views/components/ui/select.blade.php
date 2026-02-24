@props([
    'label' => null,
    'name' => '',
    'options' => [],
    'selected' => null,
    'placeholder' => 'Pilih...',
    'required' => false,
    'disabled' => false,
    'error' => null,
    'help' => null,
    'searchable' => false,
    'multiple' => false,
])

<div class="space-y-1">
    @if($label)
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
        {{ $label }}
        @if($required)
        <span class="text-danger-500">*</span>
        @endif
    </label>
    @endif

    <select
        id="{{ $name }}"
        name="{{ $name }}"
        @if($required) required @endif
        @if($disabled) disabled @endif
        @if($multiple) multiple @endif
        @if($searchable) data-tom-select @endif
        {{ $attributes->merge([
            'class' => implode(' ', [
                'block w-full rounded-lg border shadow-sm transition-colors duration-200',
                'focus:outline-none focus:ring-2 focus:ring-offset-0',
                'disabled:bg-gray-50 disabled:text-gray-500 disabled:cursor-not-allowed',
                'dark:bg-gray-800 dark:border-gray-600 dark:text-gray-100',
                'dark:disabled:bg-gray-700 dark:disabled:text-gray-500',
                'px-3 py-2 text-sm',
                $error 
                    ? 'border-danger-300 text-danger-900 focus:border-danger-500 focus:ring-danger-500 dark:border-danger-700 dark:text-danger-400' 
                    : 'border-gray-300 focus:border-primary-500 focus:ring-primary-500 dark:focus:border-primary-400 dark:focus:ring-primary-400'
            ])
        ]) }}
    >
        @if($placeholder && !$multiple)
        <option value="">{{ $placeholder }}</option>
        @endif
        
        @foreach($options as $value => $optionLabel)
        <option value="{{ $value }}" @if($value == $selected) selected @endif>
            {{ $optionLabel }}
        </option>
        @endforeach

        {{ $slot }}
    </select>

    @if($help && !$error)
    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $help }}</p>
    @endif

    @if($error)
    <p class="text-xs text-danger-600 dark:text-danger-400 flex items-center">
        <x-ui.icon name="exclamation-circle" class="w-4 h-4 mr-1" />
        {{ $error }}
    </p>
    @endif
</div>
