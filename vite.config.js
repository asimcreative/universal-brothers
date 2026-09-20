import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/scss/app.scss',
                'resources/scss/assistant-widget.scss',
                // The motion vocabulary is its own entry because both
                // layouts need it and they share no stylesheet: the older
                // pages load Bootstrap, the homepage loads site.css.
                'resources/css/motion.css',
                // The same header on the pages that still load Bootstrap.
                'resources/css/header.css',
                'resources/css/site.css',
                'resources/js/app.js',
                'resources/js/admin.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
