import '@fontsource-variable/nunito';
import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
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
                h(
                    Suspense,
                    null,
                    {
                        default: () => h(App, props),
                        // Minimal fallback to satisfy Suspense during async setup
                        fallback: () => h('div', { class: 'inertia-suspense-fallback' }, 'Loading...'),
                    }
                ),
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

        // Return the app instance to satisfy Inertia's expected type
        return vueApp;
    },
    progress: {
        color: '#4B5563',
    },
});
