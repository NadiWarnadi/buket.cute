import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import purgecss from 'vite-plugin-purgecss';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/admin.css',
                'resources/js/admin.js',
                'resources/css/public-style.css',
                'resources/js/public-script.js',
                'resources/css/chat-wa.css'
            ],
            refresh: true,
        }),
        purgecss({
            content: [
                './resources/**/*.blade.php',
                './resources/**/*.js',
                './resources/**/*.vue',
                './storage/framework/views/*.php', // 1. TAMBAHKAN INI agar file cache Laravel ikut diperiksa
            ],
            safelist: {
                // 2. HAPUS 'greedy' dan masukkan 'bi' ke sini agar ikon yang tidak dipakai BISA DIHAPUS otomatis
                standard: ['show', 'fade', 'collapsed', 'collapsing', 'active', 'scrolled', 'bi'],
            }
        }),
    ],
    resolve: {
        alias: {
            '/images': '/public/images',
        },
    },
});
