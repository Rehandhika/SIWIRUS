{{-- Test file for app layout refactoring --}}
{{-- This file demonstrates the refactored app.blade.php layout --}}

<x-app-layout>
    <div class="space-y-6">
        <h1 class="text-2xl font-bold text-gray-900">App Layout Test Page</h1>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Layout Features Tested</h2>
            
            <div class="space-y-4">
                <div class="flex items-start space-x-3">
                    <x-ui.icon name="check-circle" class="w-5 h-5 text-success-500 flex-shrink-0 mt-0.5" />
                    <div>
                        <p class="font-medium text-gray-900">Avatar Component Integration</p>
                        <p class="text-sm text-gray-600">User avatar in sidebar now uses <code class="bg-gray-100 px-1 rounded">x-ui.avatar</code> component</p>
                    </div>
                </div>
                
                <div class="flex items-start space-x-3">
                    <x-ui.icon name="check-circle" class="w-5 h-5 text-success-500 flex-shrink-0 mt-0.5" />
                    <div>
                        <p class="font-medium text-gray-900">Icon Component Usage</p>
                        <p class="text-sm text-gray-600">All icons replaced with <code class="bg-gray-100 px-1 rounded">x-ui.icon</code> component for consistency</p>
                    </div>
                </div>
                
                <div class="flex items-start space-x-3">
                    <x-ui.icon name="check-circle" class="w-5 h-5 text-success-500 flex-shrink-0 mt-0.5" />
                    <div>
                        <p class="font-medium text-gray-900">Semantic HTML</p>
                        <p class="text-sm text-gray-600">Added semantic elements: <code class="bg-gray-100 px-1 rounded">aside</code>, <code class="bg-gray-100 px-1 rounded">header</code>, <code class="bg-gray-100 px-1 rounded">main</code></p>
                    </div>
                </div>
                
                <div class="flex items-start space-x-3">
                    <x-ui.icon name="check-circle" class="w-5 h-5 text-success-500 flex-shrink-0 mt-0.5" />
                    <div>
                        <p class="font-medium text-gray-900">Accessibility Improvements</p>
                        <p class="text-sm text-gray-600">Added ARIA labels, roles, and improved focus states</p>
                    </div>
                </div>
                
                <div class="flex items-start space-x-3">
                    <x-ui.icon name="check-circle" class="w-5 h-5 text-success-500 flex-shrink-0 mt-0.5" />
                    <div>
                        <p class="font-medium text-gray-900">Toast Notification Styling</p>
                        <p class="text-sm text-gray-600">Updated to use design system colors (success-*, danger-*, warning-*, info-*)</p>
                    </div>
                </div>
                
                <div class="flex items-start space-x-3">
                    <x-ui.icon name="check-circle" class="w-5 h-5 text-success-500 flex-shrink-0 mt-0.5" />
                    <div>
                        <p class="font-medium text-gray-900">Responsive Behavior</p>
                        <p class="text-sm text-gray-600">Mobile sidebar with backdrop, smooth transitions, and proper z-index layering</p>
                    </div>
                </div>
                
                <div class="flex items-start space-x-3">
                    <x-ui.icon name="check-circle" class="w-5 h-5 text-success-500 flex-shrink-0 mt-0.5" />
                    <div>
                        <p class="font-medium text-gray-900">Design System Colors</p>
                        <p class="text-sm text-gray-600">Replaced hardcoded colors (blue-*) with theme colors (primary-*)</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Test Toast Notifications</h2>
            <p class="text-sm text-gray-600 mb-4">Click the buttons below to test toast notifications:</p>
            
            <div class="flex flex-wrap gap-3">
                <button @click="$dispatch('alert', { message: 'Success notification!', type: 'success' })"
                        class="px-4 py-2 bg-success-500 hover:bg-success-700 text-white rounded-lg transition-colors">
                    Test Success
                </button>
                
                <button @click="$dispatch('alert', { message: 'Error notification!', type: 'error' })"
                        class="px-4 py-2 bg-danger-500 hover:bg-danger-700 text-white rounded-lg transition-colors">
                    Test Error
                </button>
                
                <button @click="$dispatch('alert', { message: 'Warning notification!', type: 'warning' })"
                        class="px-4 py-2 bg-warning-500 hover:bg-warning-700 text-white rounded-lg transition-colors">
                    Test Warning
                </button>
                
                <button @click="$dispatch('alert', { message: 'Info notification!', type: 'info' })"
                        class="px-4 py-2 bg-info-500 hover:bg-info-700 text-white rounded-lg transition-colors">
                    Test Info
                </button>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Responsive Testing</h2>
            <p class="text-sm text-gray-600 mb-4">Resize your browser window to test responsive behavior:</p>
            
            <ul class="space-y-2 text-sm text-gray-700">
                <li class="flex items-center space-x-2">
                    <x-ui.icon name="device-phone-mobile" class="w-4 h-4 text-gray-400" />
                    <span><strong>Mobile (&lt; 768px):</strong> Hamburger menu, collapsible sidebar with backdrop</span>
                </li>
                <li class="flex items-center space-x-2">
                    <x-ui.icon name="device-tablet" class="w-4 h-4 text-gray-400" />
                    <span><strong>Tablet (768px - 1024px):</strong> Fixed sidebar visible</span>
                </li>
                <li class="flex items-center space-x-2">
                    <x-ui.icon name="computer-desktop" class="w-4 h-4 text-gray-400" />
                    <span><strong>Desktop (&gt; 1024px):</strong> Fixed sidebar with full navigation</span>
                </li>
            </ul>
        </div>
    </div>
</x-app-layout>
