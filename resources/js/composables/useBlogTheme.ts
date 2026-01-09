import { useAppearance } from '@/composables/useAppearance';
import { getFontCorrection, SCALE_TO_FONT_MAP, SPECIAL_KEY_MAPPINGS } from '@/constants/fonts';
import type { BlogTheme } from '@/types/blog.types';
import { useMediaQuery } from '@vueuse/core';
import { computed, type ComputedRef } from 'vue';

function mapThemeKeyToStyles(key: string, value: string, style: Record<string, string>, currentTheme: Record<string, string>): void {
    let finalValue = value;

    // Apply font correction if it's a scale key
    if (key in SCALE_TO_FONT_MAP) {
        const fontKey = SCALE_TO_FONT_MAP[key];
        const fontValue = currentTheme[fontKey] || 'inherit';
        const correction = getFontCorrection(fontValue);
        finalValue = (parseFloat(value) * correction).toString();
    }

    style[key] = finalValue;

    // Handle special key mappings
    if (key in SPECIAL_KEY_MAPPINGS) {
        style[SPECIAL_KEY_MAPPINGS[key]] = finalValue;
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
        const currentTheme = (isDark.value ? theme.value?.dark : theme.value?.light) || {};

        const style: Record<string, string> = {};
        for (const [key, value] of Object.entries(currentTheme)) {
            if (value) {
                mapThemeKeyToStyles(key, value, style, currentTheme);
            }
        }
        return style;
    });

    return { isDark, mergedThemeStyle };
}
