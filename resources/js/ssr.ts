import { createInertiaApp } from '@inertiajs/vue3';
import createServer from '@inertiajs/vue3/server';
import { renderToString } from '@vue/server-renderer';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { App, DefineComponent } from 'vue';
import { createSSRApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
// IMPORTANT: do NOT import your app's './i18n' here for SSR.
// Build a minimal SSR-safe i18n instance instead.
import type { Page } from '@inertiajs/core';
import { createI18n } from 'vue-i18n';
import type { AppPageProps } from './types';

// Constants for default values to avoid magic strings and duplication
const DEFAULT_LOCALE = 'en';
const DEFAULT_ZIGGY_URL = 'http://localhost';

/**
 * Sets up global process error handlers for better SSR error visibility.
 */
function setupProcessErrorHandling(): void {
    process.on('unhandledRejection', (reason: any) => {
        console.error('SSR unhandledRejection:', reason?.stack || reason);
    });
    process.on('uncaughtException', (err: any) => {
        console.error('SSR uncaughtException:', err?.stack || err);
    });
}

/**
 * Configures Vue-specific error and warning handlers for the app instance.
 */
function configureVueErrorHandlers(app: App): void {
    // Vue-level error handler to capture component setup/runtime failures
    app.config.errorHandler = (err, instance, info) => {
        console.error('SSR Vue errorHandler:', info, (err as any)?.stack || err);
        throw err; // rethrow so Inertia logs it too
    };
    app.config.warnHandler = (msg, instance, trace) => {
        console.warn('SSR Vue warnHandler:', msg, trace);
    };
}

/**
 * Creates a minimal, SSR-safe i18n instance.
 * @param pageProps - The page properties containing locale information.
 */
function createSsrI18nInstance(pageProps: AppPageProps) {
    // Create a minimal SSR-safe i18n instance (no localStorage, no window access)
    const initialLocale = (pageProps as any)?.locale || DEFAULT_LOCALE;
    const provided = (pageProps as any)?.translations as { locale?: string; messages?: Record<string, any> } | undefined;
    const locale = provided?.locale || initialLocale;
    const messages = provided?.messages ? { [locale]: provided.messages } : {};
    return createI18n({
        legacy: false,
        locale,
        fallbackLocale: DEFAULT_LOCALE,
        messages,
    });
}

/**
 * Creates the Ziggy configuration for SSR, ensuring a valid URL.
 * @param pageProps - The page properties containing Ziggy configuration.
 */
function getZiggySsrConfig(pageProps: AppPageProps) {
    const ziggyProps = pageProps.ziggy ?? {};
    let ziggyLocation: URL;
    try {
        ziggyLocation = new URL(ziggyProps.location ?? DEFAULT_ZIGGY_URL);
    } catch {
        ziggyLocation = new URL(DEFAULT_ZIGGY_URL);
    }
    return {
        ...ziggyProps,
        location: ziggyLocation,
    };
}

setupProcessErrorHandling();

createServer((page: Page) =>
    createInertiaApp({
        page,
        render: renderToString,
        resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
        setup({ App, props, plugin }) {
            const app = createSSRApp({ render: () => h(App, props) });
            const pageProps = page.props as AppPageProps;

            configureVueErrorHandlers(app);

            const i18n = createSsrI18nInstance(pageProps);
            const ziggyConfig = getZiggySsrConfig(pageProps);

            app.use(plugin).use(ZiggyVue, ziggyConfig).use(i18n);

            // Provide base URL for SSR fetches (if you later need to fetch translations on server)
            (globalThis as any).__ziggyLocation = pageProps.ziggy?.location ?? DEFAULT_ZIGGY_URL;

            return app;
        },
        progress: false,
    }),
);
