import { defineConfig } from 'vite';
import { fileURLToPath } from 'node:url';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    plugins: [
        react(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/react/main.jsx'],
            refresh: [
                'resources/views/**',
                'app/Livewire/**',
            ],
        }),
        tailwindcss(),
    ],
    build: {
        chunkSizeWarningLimit: 1000,
        rollupOptions: {
            output: {
                manualChunks: {
                    charts: [
                        'chart.js',
                    ],
                    forms: [
                        'flatpickr',
                        'tom-select',
                    ],
                    utils: [
                        'sortablejs',
                        'filepond',
                    ],
                },
            },
        },
    },
});
