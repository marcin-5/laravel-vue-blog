import '@fontsource-variable/montserrat';
import '@fontsource-variable/nunito';
import '@fontsource-variable/quicksand';
import '@fontsource-variable/recursive';
import '@fontsource-variable/roboto';
import '@fontsource-variable/rokkitt';
import '@fontsource/esteban';
import '@fontsource/inter';
import '@fontsource/noto-serif';
import '@fontsource/old-standard-tt';
import '@fontsource/raleway';
import '@fontsource/slabo-27px';
import '../css/app.css';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h, Suspense } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { initializeTheme } from './composables/useAppearance';
import { i18n, setLocale } from './i18n';

// Better type safety for props
interface InertiaPageProps {
    initialPage?: {
        props?: {
            locale?: string;
        };
    };
}

// Move theme initialization before app creation to prevent FOUC
initializeTheme();

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        if (!el) {
            console.error('Mount element not found');
            return;
        }

        const vueApp = createApp({
            render: () =>
                h(Suspense, null, {
                    default: () => h(App, props),
                    // Minimal fallback to satisfy Suspense during async setup
                    fallback: () => h('div', { class: 'inertia-suspense-fallback' }, 'Loading...'),
                }),
        })
            .use(plugin)
            .use(ZiggyVue)
            .use(i18n);

        // Better type safety for locale extraction
        const typedProps = props as InertiaPageProps;
        const pageProps: any = (props as any)?.initialPage?.props || (props as any)?.page?.props || {};
        const initialLocale = (typedProps.initialPage?.props?.locale || (typedProps as any)?.page?.props?.locale || 'en') as string;

        // If server provided translations, hydrate them synchronously to avoid blinking
        const provided = pageProps?.translations as { locale?: string; messages?: Record<string, any> } | undefined;
        try {
            if (provided?.messages) {
                const loc = provided.locale || initialLocale;
                i18n.global.setLocaleMessage(loc, provided.messages);
                i18n.global.locale.value = loc;
            } else {
                i18n.global.locale.value = initialLocale;
            }
            // Also ensure our helper state is aligned
            void setLocale(i18n.global.locale.value).catch((error) => {
                console.warn('Failed to set initial locale, using fallback:', error);
            });
        } catch (error) {
            console.warn('Failed to set initial locale/messages, using fallback:', error);
        }

        vueApp.mount(el);

        // Merge server-provided translations on every successful Inertia navigation
        try {
            const handlePageTranslations = (page: any) => {
                const props = page?.props || {};
                const provided = props?.translations as { locale?: string; messages?: Record<string, any> } | undefined;
                const nextLocale = (props?.locale as string) || i18n.global.locale.value || 'en';

                if (provided?.messages) {
                    const loc = provided.locale || nextLocale;
                    try {
                        // Clear previous messages to ensure we only have what is needed for this part of the app
                        i18n.global.setLocaleMessage(loc, provided.messages);
                        i18n.global.locale.value = loc;
                    } catch (e) {
                        console.warn('Failed to merge navigation translations, continuing:', e);
                    }
                } else if (nextLocale && nextLocale !== i18n.global.locale.value) {
                    i18n.global.locale.value = nextLocale;
                }
                // Keep helper in sync
                void setLocale(i18n.global.locale.value).catch(() => {});
            };

            // Prefer 'success' event which exposes the resolved page
            router.on('success', (event: any) => handlePageTranslations(event.detail.page));
            // Fallback for older adapters: try 'navigate'
            router.on('navigate', (event: any) => handlePageTranslations(event.detail.page));
        } catch (e) {
            console.warn('Failed to attach Inertia navigation listeners for translations:', e);
        }

        // Return the app instance to satisfy Inertia's expected type
        return vueApp;
    },
    progress: {
        color: '#4B5563',
    },
});
