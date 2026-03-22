import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { globSync } from 'glob';
// import path from 'path';

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
        server: {
            host: '0.0.0.0',       // escuta em todas as interfaces do container
            port: Number(env.VITE_PORT) || 5173,
            strictPort: true,
            hmr: {
                host: 'localhost', // endereço que o BROWSER vai usar para conectar
                port: Number(env.VITE_PORT) || 5173,
            },
        },
        resolve: {
            alias: {
                '@': '/resources/js',
                // 'ziggy-js': path.resolve('vendor/tightenco/ziggy'),
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