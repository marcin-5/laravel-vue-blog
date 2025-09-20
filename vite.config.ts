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
    // Ensure Vue and vue-i18n feature flags are defined during both client and SSR builds
    define: {
        __VUE_OPTIONS_API__: true,
        __VUE_PROD_DEVTOOLS__: false,
        __VUE_I18N_FULL_INSTALL__: true,
        __VUE_I18N_LEGACY_API__: false,
        __INTLIFY_PROD_DEVTOOLS__: false,
    },
    // Bundle vue-i18n into the SSR build so the above defines are applied inside the dependency
    ssr: {
        noExternal: ['vue-i18n', '@intlify/message-compiler'],
    },
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
