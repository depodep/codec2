import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
   ],
    server: {
        host: '0.0.0.0', // Nagpapahintulot ng external network connection
        hmr: {
            host: '192.168.100.14', // Ilagay dito ang IP address ng PC mo
        },
    },
});
