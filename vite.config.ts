import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

const appUrl = process.env.APP_URL ?? '';
const isHttps = appUrl.startsWith('https://') || process.env.VITE_DEV_HTTPS === 'true';
const hmrHost = process.env.VITE_HMR_HOST ?? (appUrl ? new URL(appUrl).hostname : 'localhost');
const hmrClientPort = process.env.VITE_HMR_CLIENT_PORT ? Number(process.env.VITE_HMR_CLIENT_PORT) : isHttps ? 443 : 5173;
const hmrProtocol = isHttps ? 'wss' : 'ws';
const usePolling = process.env.VITE_POLLING === 'true';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
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
        host: true,
        watch: usePolling ? { usePolling: true } : undefined,
        hmr: {
            host: hmrHost,
            protocol: hmrProtocol,
            clientPort: hmrClientPort,
        },
    },
});
