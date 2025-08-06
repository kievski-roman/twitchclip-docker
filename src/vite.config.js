// vite.config.js
import { defineConfig } from 'vite'
import laravel  from 'laravel-vite-plugin'
import react    from '@vitejs/plugin-react'

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',          // Tailwind
                'resources/js/alpine.js',         // Alpine// React entry
            ],
            refresh: true,
        }),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        hmr:  { host: 'localhost', port: 5173 },
        cors: {
            origin: 'http://localhost:8088',
            credentials: true,
        },
    },
})
