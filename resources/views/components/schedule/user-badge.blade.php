@props([
    'assignment',
    'showRemove' => false,
])

@php
    $userName = $assignment['user_name'] ?? 'Unknown';
    $userId = $assignment['user_id'] ?? null;
    $userPhoto = $assignment['user_photo'] ?? null;
    $userStatus = $assignment['user_status'] ?? 'active';
    $assignmentId = $assignment['id'] ?? null;
    $editedAt = $assignment['edited_at'] ?? null;
    
    // Get initials for avatar
    $nameParts = explode(' ', $userName);
    $initials = '';
    foreach ($nameParts as $part) {
        if (!empty($part)) {
            $initials .= strtoupper(substr($part, 0, 1));
            if (strlen($initials) >= 2) break;
        }
    }
    
    // Enhanced avatar colors with gradients
    $avatarColors = [
        'bg-gradient-to-br from-blue-400 to-blue-600 text-white',
        'bg-gradient-to-br from-green-400 to-green-600 text-white',
        'bg-gradient-to-br from-blue-400 to-blue-600 text-white',
        'bg-gradient-to-br from-pink-400 to-pink-600 text-white',
        'bg-gradient-to-br from-yellow-400 to-yellow-600 text-white',
        'bg-gradient-to-br from-red-400 to-red-600 text-white',
        'bg-gradient-to-br from-blue-400 to-blue-600 text-white',
        'bg-gradient-to-br from-teal-400 to-teal-600 text-white',
    ];
    
    // Select color based on user ID or name hash
    $colorIndex = ($userId ?? crc32($userName)) % count($avatarColors);
    $avatarColor = $userStatus === 'active' ? $avatarColors[$colorIndex] : 'bg-gradient-to-br from-gray-400 to-gray-600 text-white';
    
    // Edited indicator
    $isEdited = !empty($editedAt);
@endphp

<div 
    class="flex items-center justify-between text-xs bg-white rounded-lg px-3 py-2 shadow-sm hover:shadow-md transition-all duration-200 group border border-gray-200 hover:border-blue-300"
    x-data
    x-tooltip="'{{ $userName }}{{ $isEdited ? ' (Edited: ' . \Carbon\Carbon::parse($editedAt)->format('d/m H:i') . ')' : '' }}'"
>
    <div class="flex items-center space-x-2.5 flex-1 min-w-0">
        <!-- Enhanced Avatar or Initials -->
        @if($userPhoto)
            <div class="relative flex-shrink-0">
                <img 
                    src="{{ $userPhoto }}" 
                    alt="{{ $userName }}"
                    class="w-7 h-7 rounded-full object-cover ring-2 ring-white shadow-sm"
                >
                @if($userStatus !== 'active')
                    <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-gray-500 rounded-full border-2 border-white"></div>
                @else
                    <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-green-500 rounded-full border-2 border-white"></div>
                @endif
            </div>
        @else
            <div class="relative flex-shrink-0">
                <div class="w-7 h-7 rounded-full {{ $avatarColor }} flex items-center justify-center text-xs font-bold shadow-sm ring-2 ring-white">
                    {{ $initials }}
                </div>
                @if($userStatus !== 'active')
                    <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-gray-500 rounded-full border-2 border-white"></div>
                @else
                    <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-green-500 rounded-full border-2 border-white"></div>
                @endif
            </div>
        @endif
        
        <!-- User Name with Better Typography -->
        <span class="truncate flex-1 font-semibold text-gray-900">
            {{ $userName }}
        </span>
        
        <!-- Edited Indicator with Animation -->
        @if($isEdited)
            <span class="text-blue-500 flex-shrink-0 animate-pulse" title="Edited">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                </svg>
            </span>
        @endif
    </div>
    
    <!-- Enhanced Remove Button with Confirmation -->
    @if($showRemove && $assignmentId)
        <button 
            @click.stop="$dispatch('confirm-remove', { assignmentId: {{ $assignmentId }}, userName: '{{ $userName }}' })"
            class="text-red-500 hover:text-white hover:bg-red-500 ml-2 opacity-0 group-hover:opacity-100 transition-all duration-200 flex-shrink-0 p-1 rounded-md hover:scale-110 active:scale-95"
            x-data
            x-tooltip="'Hapus {{ $userName }} dari slot'"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
        </button>
    @endif
</div>
