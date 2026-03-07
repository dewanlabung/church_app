import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import { VitePWA } from 'vite-plugin-pwa';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.tsx',
            ],
            refresh: true,
        }),
        react(),
        VitePWA({
            registerType: 'autoUpdate',
            devOptions: { enabled: true },
            manifest: false, // manifest served dynamically from /api/v1/pwa-config
            workbox: {
                globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2}'],
                runtimeCaching: [
                    {
                        urlPattern: /^\/api\/v1\//,
                        handler: 'NetworkFirst',
                        options: {
                            cacheName: 'api-cache',
                            expiration: { maxEntries: 200, maxAgeSeconds: 60 * 60 * 24 },
                            networkTimeoutSeconds: 10,
                        },
                    },
                    {
                        urlPattern: /\.(png|jpg|jpeg|svg|gif|webp)$/,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'image-cache',
                            expiration: { maxEntries: 500, maxAgeSeconds: 60 * 60 * 24 * 30 },
                        },
                    },
                ],
            },
            injectRegister: 'auto',
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
        },
    },
});
