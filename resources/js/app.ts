import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h, Suspense } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { initializeTheme } from './composables/useAppearance';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    async setup({ el, App, props, plugin }) {
        const vueApp = createApp({
            render: () => h(Suspense, {}, {
                default: () => h(App, props),
                fallback: () => h('div', { class: 'flex items-center justify-center min-h-screen' }, 'Loading...')
            })
        })
            .use(plugin)
            .use(ZiggyVue)
            .use((await import('./i18n')).i18n);

        const { setLocale } = await import('./i18n');
        const initialLocale = (props as any).initialPage?.props?.locale || (props as any).page?.props?.locale || 'en';
        setLocale(initialLocale).catch(console.error);

        vueApp.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
