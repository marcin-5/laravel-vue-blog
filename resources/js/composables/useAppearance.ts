import type { Appearance, AppPageProps } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

export type { Appearance };

export function updateTheme(value: Appearance) {
    if (typeof window === 'undefined') {
        return;
    }

    if (value === 'system') {
        const mediaQueryList = window.matchMedia('(prefers-color-scheme: dark)');
        const systemTheme = mediaQueryList.matches ? 'dark' : 'light';

        document.documentElement.classList.toggle('dark', systemTheme === 'dark');
    } else {
        document.documentElement.classList.toggle('dark', value === 'dark');
    }
}

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;

    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const mediaQuery = () => {
    if (typeof window === 'undefined') {
        return null;
    }

    return window.matchMedia('(prefers-color-scheme: dark)');
};

const getStoredAppearance = (): Appearance | null => {
    if (typeof window === 'undefined' || typeof localStorage === 'undefined') {
        return null;
    }

    return localStorage.getItem('appearance') as Appearance | null;
};

const handleSystemThemeChange = () => {
    const currentAppearance = getStoredAppearance();

    updateTheme(currentAppearance || 'system');
};

export function initializeTheme() {
    if (typeof window === 'undefined') {
        return;
    }

    // Initialize theme from saved preference or default to system...
    const savedAppearance = getStoredAppearance();
    if (savedAppearance) {
        setCookie('appearance', savedAppearance);
    }
    updateTheme(savedAppearance || 'system');

    // Set up system theme change listener...
    const mq = mediaQuery();
    mq?.addEventListener('change', handleSystemThemeChange);

    return () => mq?.removeEventListener('change', handleSystemThemeChange);
}

// Singleton state — shared across all component instances on client
const clientAppearance = ref<Appearance>(getStoredAppearance() || 'system');

export function useAppearance() {
    if (typeof window === 'undefined') {
        try {
            const page = usePage<AppPageProps>();
            return {
                appearance: computed<Appearance>(() => ((page?.props as any)?.appearance as Appearance) || 'system'),
                updateAppearance: () => {},
            };
        } catch {
            return {
                appearance: ref<Appearance>('system'),
                updateAppearance: () => {},
            };
        }
    }

    if (getStoredAppearance() === null) {
        try {
            const page = usePage<AppPageProps>();
            const pageAppearance = (page?.props as any)?.appearance as Appearance | undefined;
            if (pageAppearance && clientAppearance.value !== pageAppearance) {
                clientAppearance.value = pageAppearance;
            }
        } catch {
            // Ignore outside Inertia context
        }
    }

    function updateAppearance(value: Appearance) {
        if (typeof window === 'undefined') {
            return;
        }

        clientAppearance.value = value;

        // Store in localStorage for client-side persistence...
        localStorage.setItem('appearance', value);

        // Store in cookie for SSR...
        setCookie('appearance', value);

        updateTheme(value);
    }

    return {
        appearance: clientAppearance,
        updateAppearance,
    };
}
