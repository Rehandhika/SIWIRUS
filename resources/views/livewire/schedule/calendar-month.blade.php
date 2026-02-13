<div class="grid grid-cols-7 gap-0">
    <!-- Day Headers -->
    <div class="bg-gray-50 p-3 text-center font-semibold text-gray-700 border-r border-b">
        Min
    </div>
    <div class="bg-gray-50 p-3 text-center font-semibold text-gray-700 border-r border-b">
        Sen
    </div>
    <div class="bg-gray-50 p-3 text-center font-semibold text-gray-700 border-r border-b">
        Sel
    </div>
    <div class="bg-gray-50 p-3 text-center font-semibold text-gray-700 border-r border-b">
        Rab
    </div>
    <div class="bg-gray-50 p-3 text-center font-semibold text-gray-700 border-r border-b">
        Kam
    </div>
    <div class="bg-gray-50 p-3 text-center font-semibold text-gray-700 border-r border-b">
        Jum
    </div>
    <div class="bg-gray-50 p-3 text-center font-semibold text-gray-700 border-b">
        Sab
    </div>

    <!-- Calendar Days -->
    @php
        $startOfMonth = $currentDate->copy()->startOfMonth();
        $endOfMonth = $currentDate->copy()->endOfMonth();
        $startDate = $startOfMonth->copy()->startOfWeek();
        $endDate = $endOfMonth->copy()->endOfWeek();
        
        $period = new DatePeriod($startDate, new DateInterval('P1D'), $endDate->addDay());
    @endphp

    @foreach($period as $date)
        @php
            $dateStr = $date->format('Y-m-d');
            $isCurrentMonth = $date->month === $currentDate->month;
            $dayData = $calendarData[$dateStr] ?? null;
            $schedules = $dayData['schedules'] ?? collect();
        @endphp

        <div class="min-h-[120px] border-r border-b {{ 
            $date->isToday() ? 'bg-blue-50' : 
            ($isCurrentMonth ? 'bg-white' : 'bg-gray-50')
        }} {{ $date->isWeekend() ? 'bg-red-50' : '' }} 
            relative group hover:bg-gray-50 transition-colors cursor-pointer"
             onclick="@this.call('handleDateClick', '{{ $dateStr }}')"
             ondrop="handleDrop(event, '{{ $dateStr }}', 1)"
             ondragover="handleDragOver(event)"
             ondragleave="handleDragLeave(event)">
            
            <!-- Date Header -->
            <div class="p-2 border-b {{ $date->isToday() ? 'bg-blue-100' : '' }}">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium {{ 
                        $isCurrentMonth ? 'text-gray-900' : 'text-gray-400'
                    }} {{ $date->isToday() ? 'text-blue-600' : '' }}">
                        {{ $date->format('d') }}
                    </span>
                    @if($dayData && $dayData['total_schedules'] > 0)
                        <x-ui.badge variant="info" size="sm">
                            {{ $dayData['total_schedules'] }}
                        </x-ui.badge>
                    @endif
                </div>
            </div>

            <!-- Schedule Items -->
            <div class="p-1 space-y-1">
                @foreach($schedules as $session => $sessionSchedules)
                    @foreach($sessionSchedules as $schedule)
                        <div class="schedule-item bg-blue-100 text-blue-800 p-1 rounded text-xs cursor-move hover:bg-blue-200 transition-colors"
                             draggable="true"
                             ondragstart="handleDragStart(event, {{ $schedule->id }})"
                             onclick="event.stopPropagation(); @this.call('handleScheduleClick', {{ $schedule->id }})">
                            <div class="flex items-center space-x-1">
                                <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                </svg>
                                <div class="truncate flex-1">
                                    <div class="font-medium">{{ $schedule->user->name }}</div>
                                    <x-ui.badge variant="info" size="sm" class="mt-0.5">Sesi {{ $session }}</x-ui.badge>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endforeach
            </div>

            <!-- Drop Zone Indicator -->
            <div class="drop-zone-indicator absolute inset-0 bg-green-100 border-2 border-green-400 rounded pointer-events-none opacity-0 transition-opacity"></div>
        </div>
    @endforeach
</div>

<!-- Drag and Drop JavaScript -->
<script>
let draggedElement = null;

function handleDragStart(event, scheduleId) {
    draggedElement = event.target;
    event.target.style.opacity = '0.5';
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/html', event.target.innerHTML);
    
    // Notify Livewire
    @this.call('handleDragStart', scheduleId);
}

function handleDragOver(event) {
    if (event.preventDefault) {
        event.preventDefault();
    }
    
    event.dataTransfer.dropEffect = 'move';
    
    // Show drop zone indicator
    const dropIndicator = event.currentTarget.querySelector('.drop-zone-indicator');
    if (dropIndicator) {
        dropIndicator.style.opacity = '1';
    }
    
    return false;
}

function handleDragLeave(event) {
    // Hide drop zone indicator
    const dropIndicator = event.currentTarget.querySelector('.drop-zone-indicator');
    if (dropIndicator) {
        dropIndicator.style.opacity = '0';
    }
}

function handleDrop(event, date, session) {
    if (event.stopPropagation) {
        event.stopPropagation();
    }
    
    // Hide drop zone indicator
    const dropIndicator = event.currentTarget.querySelector('.drop-zone-indicator');
    if (dropIndicator) {
        dropIndicator.style.opacity = '0';
    }
    
    // Restore dragged element opacity
    if (draggedElement) {
        draggedElement.style.opacity = '';
    }
    
    // Notify Livewire about the drop
    @this.call('handleDrop', date, session);
    
    return false;
}

// Clean up drag state
document.addEventListener('dragend', function(event) {
    if (draggedElement) {
        draggedElement.style.opacity = '';
    }
    draggedElement = null;
    
    // Hide all drop indicators
    document.querySelectorAll('.drop-zone-indicator').forEach(indicator => {
        indicator.style.opacity = '0';
    });
});
</script>
