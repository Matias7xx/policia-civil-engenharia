import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { globSync } from 'glob';

//todos os arquivos .vue dentro de resources/js/Pages
const pageFiles = globSync('resources/js/Pages/**/*.vue');

export default defineConfig({
    plugins: [
        laravel({
            input: [ 'resources/js/app.js',
                ...pageFiles, //carregando todas as PAGES (necessário para produção!!)
            ],
            refresh: true,
            publicDirectory: 'public',
            buildDirectory: 'build', // Isso cria /public/build
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
    build: {
        chunkSizeWarningLimit: 1000,
    }
});
