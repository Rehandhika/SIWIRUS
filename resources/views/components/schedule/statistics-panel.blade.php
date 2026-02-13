@props([
    'statistics' => [],
    'show' => true,
])

@php
    $filledSlots = $statistics['filled_slots'] ?? 0;
    $totalSlots = $statistics['total_slots'] ?? 12;
    $emptySlots = $statistics['empty_slots'] ?? 0;
    $totalAssignments = $statistics['total_assignments'] ?? 0;
    $coverageRate = $statistics['coverage_rate'] ?? 0;
    $avgUsersPerSlot = $statistics['avg_users_per_slot'] ?? 0;
    $avgUsersPerFilledSlot = $statistics['avg_users_per_filled_slot'] ?? 0;
    
    // Determine coverage color and status
    $coverageColor = 'text-gray-900';
    $coverageBg = 'from-gray-50 to-gray-100';
    $coverageStatus = 'Perlu Ditingkatkan';
    $coverageIcon = '⚠️';
    
    if ($coverageRate >= 80) {
        $coverageColor = 'text-green-600';
        $coverageBg = 'from-green-50 to-green-100';
        $coverageStatus = 'Sangat Baik';
        $coverageIcon = '✅';
    } elseif ($coverageRate >= 50) {
        $coverageColor = 'text-yellow-600';
        $coverageBg = 'from-yellow-50 to-yellow-100';
        $coverageStatus = 'Cukup';
        $coverageIcon = '⚡';
    } else {
        $coverageColor = 'text-red-600';
        $coverageBg = 'from-red-50 to-red-100';
        $coverageIcon = '❌';
    }
    
    // Calculate percentage for filled slots
    $filledPercentage = $totalSlots > 0 ? ($filledSlots / $totalSlots) * 100 : 0;
@endphp

<div class="bg-white rounded-xl shadow-xl border border-gray-200 overflow-hidden">
    <!-- Enhanced Header with Gradient -->
    <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-5">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center shadow-lg">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <div class="text-white">
                    <h3 class="text-xl font-bold">Statistik Jadwal</h3>
                    <p class="text-sm text-blue-100">Ringkasan distribusi slot dan assignment</p>
                </div>
            </div>
            <button 
                {{ $attributes->merge(['class' => 'px-4 py-2 bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-lg text-white font-medium transition-all duration-200 border border-white/20']) }}
            >
                <div class="flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-show="{{ $show ? 'true' : 'false' }}">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                    </svg>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-show="{{ $show ? 'false' : 'true' }}">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                    <span>{{ $show ? 'Sembunyikan' : 'Tampilkan' }}</span>
                </div>
            </button>
        </div>
    </div>

    <!-- Enhanced Statistics Grid -->
    @if($show)
        <div class="px-6 py-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Filled Slots with Progress Bar -->
                <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-blue-50 to-blue-100 p-5 shadow-md hover:shadow-lg transition-shadow duration-200">
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-4xl font-bold text-blue-600">
                                {{ $filledSlots }}
                            </div>
                            <div class="text-2xl">📊</div>
                        </div>
                        <div class="text-sm text-gray-700 font-semibold mb-1">Slot Terisi</div>
                        <div class="text-xs text-gray-600">dari {{ $totalSlots }} total slot</div>
                        
                        <!-- Progress Bar -->
                        <div class="mt-3 bg-blue-200 rounded-full h-2 overflow-hidden">
                            <div class="bg-blue-600 h-full rounded-full transition-all duration-500" style="width: {{ $filledPercentage }}%"></div>
                        </div>
                        <div class="text-xs text-gray-600 mt-1">{{ $emptySlots }} slot kosong</div>
                    </div>
                </div>

                <!-- Coverage Rate with Icon -->
                <div class="relative overflow-hidden rounded-xl bg-gradient-to-br {{ $coverageBg }} p-5 shadow-md hover:shadow-lg transition-shadow duration-200">
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-4xl font-bold {{ $coverageColor }}">
                                {{ number_format($coverageRate, 1) }}%
                            </div>
                            <div class="text-2xl">{{ $coverageIcon }}</div>
                        </div>
                        <div class="text-sm text-gray-700 font-semibold mb-1">Coverage Rate</div>
                        <div class="flex items-center space-x-2 mt-2">
                            <span class="px-2 py-1 {{ $coverageColor }} bg-white rounded-full text-xs font-bold shadow-sm">
                                {{ $coverageStatus }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Total Assignments -->
                <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-blue-50 to-blue-100 p-5 shadow-md hover:shadow-lg transition-shadow duration-200">
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-4xl font-bold text-blue-600">
                                {{ $totalAssignments }}
                            </div>
                            <div class="text-2xl">👥</div>
                        </div>
                        <div class="text-sm text-gray-700 font-semibold mb-1">Total Assignments</div>
                        <div class="text-xs text-gray-600">Semua penugasan user</div>
                    </div>
                </div>

                <!-- Average Users per Slot -->
                <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-orange-50 to-orange-100 p-5 shadow-md hover:shadow-lg transition-shadow duration-200">
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-4xl font-bold text-orange-600">
                                {{ number_format($avgUsersPerSlot, 1) }}
                            </div>
                            <div class="text-2xl">📈</div>
                        </div>
                        <div class="text-sm text-gray-700 font-semibold mb-1">Avg Users/Slot</div>
                        <div class="text-xs text-gray-600">
                            {{ number_format($avgUsersPerFilledSlot, 1) }} per filled slot
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enhanced Additional Insights -->
            <div class="mt-6 pt-6 border-t border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="flex items-center space-x-3 p-3 bg-green-50 rounded-lg">
                        <div class="w-10 h-10 rounded-full bg-green-500 flex items-center justify-center flex-shrink-0 shadow-md">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-lg font-bold text-green-700">{{ $filledSlots }}</div>
                            <div class="text-xs text-gray-600">Slot dengan user</div>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                        <div class="w-10 h-10 rounded-full bg-gray-400 flex items-center justify-center flex-shrink-0 shadow-md">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM7 9a1 1 0 000 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-lg font-bold text-gray-700">{{ $emptySlots }}</div>
                            <div class="text-xs text-gray-600">Slot kosong</div>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-3 p-3 bg-blue-50 rounded-lg">
                        <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center flex-shrink-0 shadow-md">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-lg font-bold text-blue-700">{{ $totalAssignments }}</div>
                            <div class="text-xs text-gray-600">Total penugasan</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
