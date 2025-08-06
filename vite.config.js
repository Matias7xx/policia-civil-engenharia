import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { globSync } from 'glob';

// Todas as páginas para garantir que estejam no manifesto
const pageFiles = globSync('resources/js/Pages/**/*.vue');

    export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd());

    return {
        plugins: [
            laravel({
                input: [
                    'resources/js/app.js',
                    'resources/css/app.css',
                    ...pageFiles // FORÇAR inclusão de todas as páginas
                ],
                refresh: true,
                publicDirectory: 'public',
                buildDirectory: 'build',
            }),
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
        ],
        resolve: {
            alias: {
                '@': '/resources/js',
            },
        },
        build: {
            chunkSizeWarningLimit: 2000,
            rollupOptions: {
                output: {
                    manualChunks: {
                        vendor: ['vue', '@inertiajs/vue3', 'axios'],
                        maps: ['leaflet'],
                    },
                },
            },
        },
        base: env.VITE_BASE_PATH || '/', //base dinâmica
    };
});