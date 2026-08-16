import { computed, onMounted, readonly, shallowRef, watch } from 'vue';

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

export function useEnneagramPreferences(locale: 'pl' | 'en') {
    const theme = shallowRef<EnneagramTheme>('theme-light-standard');
    const hydrated = shallowRef(false);

    onMounted(() => {
        const storedTheme = window.localStorage.getItem(themeStorageKey);

        if (storedTheme && enneagramThemes.includes(storedTheme as EnneagramTheme)) {
            theme.value = storedTheme as EnneagramTheme;
        }

        hydrated.value = true;
    });

    watch(theme, (value) => {
        if (hydrated.value) {
            window.localStorage.setItem(themeStorageKey, value);
        }
    });

    const localeLabel = computed(() => (locale === 'pl' ? 'PL' : 'EN'));

    function setTheme(value: EnneagramTheme): void {
        theme.value = value;
    }

    return {
        theme: readonly(theme),
        localeLabel,
        setTheme,
    };
}