<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Notifikasi</h2>
            <p class="text-sm text-gray-500 mt-1">{{ $stats['unread'] }} notifikasi belum dibaca</p>
        </div>
        <div class="flex items-center gap-2">
            @if($stats['unread'] > 0)
                <x-ui.button 
                    wire:click="markAllAsRead" 
                    variant="white" 
                    size="sm"
                    icon="check-circle"
                >
                    Tandai Semua Dibaca
                </x-ui.button>
            @endif
            @if($stats['total'] > 0)
                <x-ui.button 
                    wire:click="deleteAll" 
                    wire:confirm="Yakin ingin menghapus semua notifikasi?" 
                    variant="white" 
                    size="sm"
                    icon="trash"
                    class="text-red-600 hover:text-red-700"
                >
                    Hapus Semua
                </x-ui.button>
            @endif
        </div>
    </div>

    <!-- Filters -->
    <x-ui.card :padding="false">
        <div class="p-4">
            <div class="flex flex-wrap gap-2">
                <button 
                    wire:click="$set('filter', 'all')" 
                    @class([
                        'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                        'bg-blue-100 text-blue-700' => $filter === 'all',
                        'text-gray-700 hover:bg-gray-100' => $filter !== 'all',
                    ])
                >
                    Semua ({{ $stats['total'] }})
                </button>
                <button 
                    wire:click="$set('filter', 'unread')" 
                    @class([
                        'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                        'bg-blue-100 text-blue-700' => $filter === 'unread',
                        'text-gray-700 hover:bg-gray-100' => $filter !== 'unread',
                    ])
                >
                    Belum Dibaca ({{ $stats['unread'] }})
                </button>
                <button 
                    wire:click="$set('filter', 'read')" 
                    @class([
                        'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                        'bg-blue-100 text-blue-700' => $filter === 'read',
                        'text-gray-700 hover:bg-gray-100' => $filter !== 'read',
                    ])
                >
                    Sudah Dibaca
                </button>
            </div>
        </div>
    </x-ui.card>

    <!-- Notifications List -->
    <div class="space-y-3">
        @forelse($notifications as $notification)
            <x-ui.card 
                :padding="false" 
                @class([
                    'hover:shadow-lg transition-shadow',
                    'opacity-75' => $notification->read_at,
                ])
            >
                <div class="p-4 sm:p-5">
                    <div class="flex items-start gap-4">
                        <!-- Icon -->
                        <div class="flex-shrink-0">
                            <div @class([
                                'w-10 h-10 rounded-full flex items-center justify-center',
                                'bg-green-100' => $notification->type === 'success',
                                'bg-yellow-100' => $notification->type === 'warning',
                                'bg-red-100' => $notification->type === 'error',
                                'bg-blue-100' => !in_array($notification->type, ['success', 'warning', 'error']),
                            ])>
                                @php
                                    $iconMap = [
                                        'success' => 'check-circle',
                                        'warning' => 'exclamation-triangle',
                                        'error' => 'x-circle',
                                        'info' => 'information-circle',
                                    ];
                                    $iconName = $iconMap[$notification->type] ?? 'information-circle';
                                    $iconColorMap = [
                                        'success' => 'text-green-600',
                                        'warning' => 'text-yellow-600',
                                        'error' => 'text-red-600',
                                        'info' => 'text-blue-600',
                                    ];
                                    $iconColor = $iconColorMap[$notification->type] ?? 'text-blue-600';
                                @endphp
                                <x-ui.icon :name="$iconName" class="w-5 h-5 {{ $iconColor }}" />
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h3 class="text-sm font-medium text-gray-900">{{ $notification->title }}</h3>
                                        @php
                                            $badgeVariantMap = [
                                                'success' => 'success',
                                                'warning' => 'warning',
                                                'error' => 'danger',
                                                'info' => 'info',
                                            ];
                                            $badgeVariant = $badgeVariantMap[$notification->type] ?? 'info';
                                        @endphp
                                        <x-ui.badge :variant="$badgeVariant" size="sm">
                                            {{ ucfirst($notification->type) }}
                                        </x-ui.badge>
                                    </div>
                                    <p class="text-sm text-gray-600 mt-1">{{ $notification->message }}</p>
                                    <p class="text-xs text-gray-400 mt-2">{{ $notification->created_at->diffForHumans() }}</p>
                                </div>
                                
                                <!-- Actions -->
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    @if(!$notification->read_at)
                                        <button 
                                            wire:click="markAsRead({{ $notification->id }})" 
                                            class="text-blue-600 hover:text-blue-800 transition-colors p-1 rounded-lg hover:bg-blue-50"
                                            title="Tandai sudah dibaca"
                                        >
                                            <x-ui.icon name="check-circle" class="w-5 h-5" />
                                        </button>
                                    @endif
                                    <button 
                                        wire:click="delete({{ $notification->id }})" 
                                        wire:confirm="Yakin ingin menghapus notifikasi ini?"
                                        class="text-red-600 hover:text-red-800 transition-colors p-1 rounded-lg hover:bg-red-50"
                                        title="Hapus"
                                    >
                                        <x-ui.icon name="trash" class="w-5 h-5" />
                                    </button>
                                </div>
                            </div>

                            <!-- Link if exists -->
                            @if($notification->link)
                                <a 
                                    href="{{ $notification->link }}" 
                                    class="inline-flex items-center text-sm text-blue-600 hover:text-blue-800 mt-3 font-medium transition-colors"
                                >
                                    Lihat Detail
                                    <x-ui.icon name="arrow-right" class="w-4 h-4 ml-1" />
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </x-ui.card>
        @empty
            <x-layout.empty-state 
                icon="bell" 
                :title="$filter === 'unread' ? 'Tidak ada notifikasi belum dibaca' : ($filter === 'read' ? 'Tidak ada notifikasi yang sudah dibaca' : 'Tidak ada notifikasi')"
                description="Notifikasi akan muncul di sini ketika ada aktivitas baru"
            />
        @endforelse
    </div>

    <!-- Pagination -->
    @if($notifications->hasPages())
        <x-data.pagination :paginator="$notifications" />
    @endif
</div>
