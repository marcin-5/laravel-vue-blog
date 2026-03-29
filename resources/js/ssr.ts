import type { Page } from '@inertiajs/core';
import { createInertiaApp } from '@inertiajs/vue3';
import { renderToString } from '@vue/server-renderer';
import type { IncomingMessage, ServerResponse } from 'http';
import { createServer as createHttpServer } from 'http';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { App, DefineComponent } from 'vue';
import { createSSRApp, h } from 'vue';
import { createI18n } from 'vue-i18n';
import { route as ziggyRoute, ZiggyVue } from 'ziggy-js';
import type { AppPageProps } from './types';

// Constants for default values to avoid magic strings and duplication
const DEFAULT_LOCALE = 'en';
const DEFAULT_ZIGGY_URL = 'http://localhost';
const SSR_PORT = Number(process.env.PORT || 13714);

const HTTP_STATUS = {
    OK: 200,
    BAD_REQUEST: 400,
    NOT_FOUND: 404,
    METHOD_NOT_ALLOWED: 405,
    INTERNAL_SERVER_ERROR: 500,
} as const;

const ROUTES = {
    HEALTH: '/health',
    SHUTDOWN: '/shutdown',
    RENDER: '/render',
} as const;

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
    app.config.errorHandler = (err, _instance, info) => {
        console.error('SSR Vue errorHandler:', info, (err as any)?.stack || err);
        throw err;
    };
    app.config.warnHandler = (msg, _instance, trace) => {
        console.warn('SSR Vue warnHandler:', msg, trace);
    };
}

/**
 * Creates a minimal, SSR-safe i18n instance.
 */
function createSsrI18nInstance(pageProps: AppPageProps) {
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
 */
function getZiggySsrConfig(pageProps: AppPageProps) {
    const ziggyProps = pageProps.ziggy ?? {};
    let ziggyLocation: URL;
    try {
        ziggyLocation = new URL(ziggyProps.location ?? DEFAULT_ZIGGY_URL);
    } catch {
        ziggyLocation = new URL(DEFAULT_ZIGGY_URL);
    }
    return { ...ziggyProps, location: ziggyLocation };
}

// ── HTTP helpers ────────────────────────────────────────────────────────────

/**
 * Sends a JSON response with the given status code and payload.
 */
function sendJson(response: ServerResponse, statusCode: number, data: unknown): void {
    response.writeHead(statusCode);
    response.end(JSON.stringify(data));
}

/**
 * Reads the full body of an incoming HTTP request as a string.
 */
function readRequestBody(req: IncomingMessage): Promise<string> {
    return new Promise((resolve, reject) => {
        let data = '';
        req.on('data', (chunk) => (data += chunk));
        req.on('end', () => resolve(data));
        req.on('error', (err) => reject(err));
    });
}

// ── Route handlers ──────────────────────────────────────────────────────────

function handleHealth(response: ServerResponse): void {
    sendJson(response, HTTP_STATUS.OK, { status: 'OK', timestamp: Date.now() });
}

function handleShutdown(response: ServerResponse): void {
    sendJson(response, HTTP_STATUS.OK, { status: 'SHUTTING_DOWN' });
    setTimeout(() => process.exit(0), 10);
}

async function handleRender(request: IncomingMessage, response: ServerResponse, renderPage: (page: Page) => Promise<any>): Promise<void> {
    if (request.method !== 'POST') {
        sendJson(response, HTTP_STATUS.METHOD_NOT_ALLOWED, { error: 'METHOD_NOT_ALLOWED' });
        return;
    }

    let body = '';
    try {
        body = await readRequestBody(request);
    } catch (e) {
        console.error('SSR read body failed:', e);
    }

    if (!body) {
        sendJson(response, HTTP_STATUS.BAD_REQUEST, { error: 'EMPTY_BODY' });
        return;
    }

    let page: Page;
    try {
        page = JSON.parse(body) as Page;
    } catch (e) {
        console.error('SSR JSON parse failed:', e);
        sendJson(response, HTTP_STATUS.BAD_REQUEST, { error: 'INVALID_JSON' });
        return;
    }

    try {
        const result = await renderPage(page);
        sendJson(response, HTTP_STATUS.OK, result);
    } catch (e: any) {
        console.error(`SSR render failed for ${page.url}:`, e?.stack || e);
        sendJson(response, HTTP_STATUS.INTERNAL_SERVER_ERROR, { error: 'RENDER_FAILED', message: e?.message });
    }
}

function handleNotFound(response: ServerResponse): void {
    sendJson(response, HTTP_STATUS.NOT_FOUND, { status: 'NOT_FOUND', timestamp: Date.now() });
}

// ── Server bootstrap ────────────────────────────────────────────────────────

function startSafeSsrServer(renderPage: (page: Page) => Promise<any>, port: number = SSR_PORT): void {
    console.log(`Starting SSR server on port ${port}...`);

    const server = createHttpServer(async (request, response) => {
        response.setHeader('Content-Type', 'application/json');
        response.setHeader('Server', 'Inertia.js SSR (safe)');

        try {
            const url = request.url || '/';

            switch (url) {
                case ROUTES.HEALTH:
                    handleHealth(response);
                    break;
                case ROUTES.SHUTDOWN:
                    handleShutdown(response);
                    break;
                case ROUTES.RENDER:
                    await handleRender(request, response, renderPage);
                    break;
                default:
                    handleNotFound(response);
            }
        } catch (e) {
            console.error('SSR request handler error:', e);
            try {
                sendJson(response, HTTP_STATUS.INTERNAL_SERVER_ERROR, { error: 'INTERNAL_ERROR' });
            } catch {
                // ignore – response may already be sent
            }
        }
    });

    server.listen(port, () => console.log('Inertia SSR server started.'));
}

// ── Entry point ─────────────────────────────────────────────────────────────

setupProcessErrorHandling();

startSafeSsrServer((page: Page) =>
    createInertiaApp({
        page,
        render: renderToString,
        resolve: (name: string) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
        setup({ App, props, plugin }: { App: any; props: any; plugin: any }) {
            const app = createSSRApp({ render: () => h(App, props) });
            const pageProps = page.props as unknown as AppPageProps;

            configureVueErrorHandlers(app);

            const i18n = createSsrI18nInstance(pageProps);
            const ziggyConfig = getZiggySsrConfig(pageProps);

            app.use(plugin).use(ZiggyVue, ziggyConfig).use(i18n);

            if (typeof (globalThis as any).route === 'undefined') {
                (globalThis as any).route = (name: any, params?: any, absolute?: any) => {
                    try {
                        return ziggyRoute(name as any, params as any, absolute as any, ziggyConfig as any);
                    } catch {
                        return '' as any;
                    }
                };
            }

            (globalThis as any).__ziggyLocation = pageProps.ziggy?.location ?? DEFAULT_ZIGGY_URL;

            return app;
        },
    }),
);
