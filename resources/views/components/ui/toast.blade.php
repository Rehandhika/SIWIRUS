@props([
    'position' => 'top-right',  // top-right, top-left, bottom-right, bottom-left
    'duration' => 3000,         // Auto-dismiss duration in ms
    'maxToasts' => 5,           // Maximum visible toasts
])

@php
$positionClasses = [
    'top-right' => 'top-4 right-4',
    'top-left' => 'top-4 left-4',
    'bottom-right' => 'bottom-4 right-4',
    'bottom-left' => 'bottom-4 left-4',
];

$positionClass = $positionClasses[$position] ?? $positionClasses['top-right'];
@endphp

<div 
    x-data="{
        init() {
            // Initialize toast store if not exists
            if (!Alpine.store('toasts')) {
                Alpine.store('toasts', {
                    items: [],
                    maxToasts: {{ $maxToasts }},
                    defaultDuration: {{ $duration }},
                    
                    add(message, type = 'success', duration = null) {
                        const id = Date.now() + Math.random();
                        const toast = { 
                            id, 
                            message, 
                            type,
                            show: true
                        };
                        
                        // Limit number of toasts
                        if (this.items.length >= this.maxToasts) {
                            this.items.shift();
                        }
                        
                        this.items.push(toast);
                        
                        // Auto-dismiss
                        const dismissDuration = duration || this.defaultDuration;
                        setTimeout(() => this.remove(id), dismissDuration);
                    },
                    
                    remove(id) {
                        const index = this.items.findIndex(t => t.id === id);
                        if (index !== -1) {
                            this.items[index].show = false;
                            // Remove from array after animation
                            setTimeout(() => {
                                this.items = this.items.filter(t => t.id !== id);
                            }, 300);
                        }
                    }
                });
            }
            
            // Listen for toast events
            window.addEventListener('toast', (event) => {
                const { message, type = 'success', duration } = event.detail;
                Alpine.store('toasts').add(message, type, duration);
            });
            
            // Listen for alert events (backward compatibility)
            window.addEventListener('alert', (event) => {
                const { message, type = 'success' } = event.detail;
                Alpine.store('toasts').add(message, type);
            });
            
            // Listen for access denied events
            window.addEventListener('show-access-denied', (event) => {
                const menu = event.detail.menu || 'ini';
                Alpine.store('toasts').add('Anda tidak memiliki akses ke menu ' + menu, 'warning');
            });
        }
    }"
    class="fixed {{ $positionClass }} z-50 pointer-events-none"
    role="region"
    aria-live="polite"
    aria-label="Notifications">
    
    <div class="flex flex-col space-y-2 max-w-sm w-full">
        <template x-for="toast in $store.toasts.items" :key="toast.id">
            <div
                x-show="toast.show"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform translate-y-2"
                x-transition:enter-end="opacity-100 transform translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform translate-y-0"
                x-transition:leave-end="opacity-0 transform translate-y-2"
                :class="{
                    'bg-emerald-50 dark:bg-emerald-900/30 border-emerald-200 dark:border-emerald-700': toast.type === 'success',
                    'bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-700': toast.type === 'error',
                    'bg-amber-50 dark:bg-amber-900/30 border-amber-200 dark:border-amber-700': toast.type === 'warning',
                    'bg-blue-50 dark:bg-blue-900/30 border-blue-200 dark:border-blue-700': toast.type === 'info'
                }"
                class="border-l-4 p-4 rounded-lg shadow-lg pointer-events-auto"
                role="alert">
                
                <div class="flex items-start">
                    <!-- Icon -->
                    <div class="flex-shrink-0">
                        <template x-if="toast.type === 'success'">
                            <x-ui.icon name="check-circle" class="h-5 w-5 text-emerald-400" />
                        </template>
                        <template x-if="toast.type === 'error'">
                            <x-ui.icon name="x-circle" class="h-5 w-5 text-red-400" />
                        </template>
                        <template x-if="toast.type === 'warning'">
                            <x-ui.icon name="exclamation-triangle" class="h-5 w-5 text-amber-400" />
                        </template>
                        <template x-if="toast.type === 'info'">
                            <x-ui.icon name="information-circle" class="h-5 w-5 text-blue-400" />
                        </template>
                    </div>
                    
                    <!-- Message -->
                    <div class="ml-3 flex-1">
                        <p 
                            :class="{
                                'text-emerald-800 dark:text-emerald-400': toast.type === 'success',
                                'text-red-800 dark:text-red-400': toast.type === 'error',
                                'text-amber-800 dark:text-amber-400': toast.type === 'warning',
                                'text-blue-800 dark:text-blue-400': toast.type === 'info'
                            }"
                            class="text-sm font-medium" 
                            x-text="toast.message">
                        </p>
                    </div>
                    
                    <!-- Close button -->
                    <button 
                        @click="$store.toasts.remove(toast.id)" 
                        type="button"
                        class="ml-auto flex-shrink-0 inline-flex text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 rounded-lg p-1 transition-colors"
                        aria-label="Close notification">
                        <x-ui.icon name="x" class="h-5 w-5" />
                    </button>
                </div>
            </div>
        </template>
    </div>
</div>
