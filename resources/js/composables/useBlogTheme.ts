import { useAppearance } from '@/composables/useAppearance';
import type { BlogTheme } from '@/types/blog.types';
import { useMediaQuery } from '@vueuse/core';
import { computed, type ComputedRef } from 'vue';

const SPECIAL_KEY_MAPPINGS: Record<string, string> = {
    '--motto-style': '--blog-motto-style',
    '--footer-scale': '--blog-footer-scale',
};

function mapThemeKeyToStyles(key: string, value: string, style: Record<string, string>): void {
    style[key] = value;

    // Handle special key mappings
    if (key in SPECIAL_KEY_MAPPINGS) {
        style[SPECIAL_KEY_MAPPINGS[key]] = value;
        return;
    }

    // Handle font keys: --font-xxx -> --blog-xxx-font
    if (key.startsWith('--font-')) {
        style[`--blog-${key.slice(7)}-font`] = value;
        return;
    }

    // Handle color keys: all other CSS variables become --color-xxx
    const isColorKey = key.startsWith('--') && !key.includes('font') && !key.includes('style') && !key.includes('scale');

    if (isColorKey) {
        style[`--color-${key.slice(2)}`] = value;
    }
}

export function useBlogTheme(theme: ComputedRef<BlogTheme | undefined>) {
    const { appearance } = useAppearance();
    const isSystemDark = useMediaQuery('(prefers-color-scheme: dark)');

    const isDark = computed(() => appearance.value === 'dark' || (appearance.value === 'system' && isSystemDark.value));

    const mergedThemeStyle = computed<Record<string, string>>(() => {
        const currentTheme = isDark.value ? theme.value?.dark : theme.value?.light;
        if (!currentTheme) return {};

        const style: Record<string, string> = {};
        for (const [key, value] of Object.entries(currentTheme)) {
            if (value) {
                mapThemeKeyToStyles(key, value, style);
            }
        }
        return style;
    });

    return { isDark, mergedThemeStyle };
}
