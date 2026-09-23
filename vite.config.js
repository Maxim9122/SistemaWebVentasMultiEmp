import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            // 'resources/css/app.css' y 'resources/js/app.js' son el
            // scaffold de Laravel que nunca se usó (el resto del sitio
            // carga Tailwind por CDN) — se dejan tal cual, sin tocar. La
            // entrada de venta-react es aparte a propósito: así este
            // experimento no toca ni un solo archivo de lo que ya
            // funciona en producción.
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/venta-react/app.jsx'],
            refresh: true,
        }),
        tailwindcss(),
        react(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
