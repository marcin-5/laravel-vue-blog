import { computed, getCurrentInstance, onMounted, readonly, shallowRef, watch } from 'vue';

export const enneagramThemes = [
    'theme-light-standard',
    'theme-light-blue',
    'theme-light-warm',
    'theme-light-pastel',
    'theme-dark-standard',
    'theme-dark-forest',
    'theme-dark-purple',
    'theme-dark-amber',
] as const;

export type EnneagramTheme = (typeof enneagramThemes)[number];

const themeStorageKey = 'enneagram-test-theme';
const themeCookieKey = 'enneagram_theme';

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === 'undefined') {
        return;
    }
    const maxAge = days * 24 * 60 * 60;
    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const getStoredLocalStorageTheme = (): EnneagramTheme | null => {
    if (typeof window === 'undefined' || typeof localStorage === 'undefined') {
        return null;
    }
    try {
        const stored = window.localStorage.getItem(themeStorageKey);
        if (stored && enneagramThemes.includes(stored as EnneagramTheme)) {
            return stored as EnneagramTheme;
        }
    } catch {
        // ignore
    }
    return null;
};

export function useEnneagramPreferences(locale: 'pl' | 'en', initialTheme?: EnneagramTheme) {
    const defaultTheme: EnneagramTheme =
        initialTheme && enneagramThemes.includes(initialTheme) ? initialTheme : getStoredLocalStorageTheme() || 'theme-light-standard';

    const theme = shallowRef<EnneagramTheme>(defaultTheme);
    const hydrated = shallowRef(false);

    const syncPreferences = () => {
        const storedTheme = getStoredLocalStorageTheme();

        if (storedTheme) {
            if (storedTheme !== theme.value) {
                theme.value = storedTheme;
            }
            setCookie(themeCookieKey, storedTheme);
        } else if (theme.value) {
            try {
                window.localStorage.setItem(themeStorageKey, theme.value);
            } catch {
                // ignore
            }
            setCookie(themeCookieKey, theme.value);
        }

        hydrated.value = true;
    };

    if (getCurrentInstance()) {
        onMounted(syncPreferences);
    } else {
        syncPreferences();
    }

    watch(theme, (value) => {
        if (typeof window !== 'undefined' && typeof localStorage !== 'undefined') {
            try {
                window.localStorage.setItem(themeStorageKey, value);
            } catch {
                // ignore
            }
        }
        setCookie(themeCookieKey, value);
    });

    const localeLabel = computed(() => (locale === 'pl' ? 'PL' : 'EN'));

    function setTheme(value: EnneagramTheme): void {
        if (enneagramThemes.includes(value)) {
            theme.value = value;
        }
    }

    return {
        theme: readonly(theme),
        localeLabel,
        setTheme,
    };
}
