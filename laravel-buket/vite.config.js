import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css',
                 'resources/js/app.js',
                 'resources/css/admin.css',
                'resources/js/admin.js',
                'resources/css/public-style.css',
                'resources/js/public-script.js',
             'resources/css/chat-wa.css'],
            refresh: true,
        }),
    ],
});
