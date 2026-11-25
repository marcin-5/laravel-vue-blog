import { useAppearance } from '@/composables/useAppearance';
import { getFontSizeCorrection, getFontWeightCorrection, NO_ALTERNATIVE_FONT, SCALE_TO_FONT_MAP, SPECIAL_KEY_MAPPINGS } from '@/constants/fonts';
import type { BlogTheme } from '@/types/blog.types';
import { useMediaQuery } from '@vueuse/core';
import { computed, ref, type ComputedRef, type Ref } from 'vue';

const ALTERNATIVE_FONT_ROLES = [
    {
        role: 'header',
        fontKey: '--font-header-alternative',
        scaleKey: '--header-scale',
        alternativeScaleKey: '--header-scale-alternative',
    },
    {
        role: 'body',
        fontKey: '--font-body-alternative',
        scaleKey: '--body-scale',
        alternativeScaleKey: '--body-scale-alternative',
    },
] as const;

function mapThemeKeyToStyles(key: string, value: string, style: Record<string, string>, currentTheme: Record<string, string>): void {
    let finalValue = value;

    // Apply font correction if it's a scale key
    if (key in SCALE_TO_FONT_MAP) {
        const fontKey = SCALE_TO_FONT_MAP[key];
        const fontValue = currentTheme[fontKey] || 'inherit';
        const correction = getFontSizeCorrection(fontValue);
        finalValue = (parseFloat(value) * correction).toString();
    }

    style[key] = finalValue;

    // Handle special key mappings
    if (key in SPECIAL_KEY_MAPPINGS) {
        style[SPECIAL_KEY_MAPPINGS[key]] = finalValue;
    }
}

function processDefinedKeys(currentTheme: Record<string, string>, style: Record<string, string>): void {
    for (const [key, value] of Object.entries(currentTheme)) {
        if (value) {
            mapThemeKeyToStyles(key, value, style, currentTheme);
        }
    }
}

function processMissingScales(currentTheme: Record<string, string>, style: Record<string, string>): void {
    for (const scaleKey of Object.keys(SCALE_TO_FONT_MAP)) {
        if (!(scaleKey in style)) {
            mapThemeKeyToStyles(scaleKey, '1', style, currentTheme);
        }
    }
}

function processFontAndColorKeys(currentTheme: Record<string, string>, style: Record<string, string>): void {
    for (const [key, value] of Object.entries(currentTheme)) {
        if (!value) {
            continue;
        }

        // Handle font keys: --font-xxx -> --blog-xxx-font
        if (key.startsWith('--font-')) {
            style[`--blog-${key.slice(7)}-font`] = value;

            // Apply font weight correction
            const weightCorrection = getFontWeightCorrection(value);
            if (weightCorrection) {
                style[`--blog-${key.slice(7)}-weight`] = weightCorrection;
            }
        }

        // Handle color keys: all other CSS variables become --color-xxx
        const isColorKey = key.startsWith('--') && !key.includes('font') && !key.includes('style') && !key.includes('scale');

        if (isColorKey && !style[`--color-${key.slice(2)}`]) {
            style[`--color-${key.slice(2)}`] = value;
        }
    }
}

export function useBlogTheme(theme: ComputedRef<BlogTheme | undefined>, useAlternativeFonts: Ref<boolean> = ref(false)) {
    const { appearance } = useAppearance();

    // SSR-safe media query
    const isSystemDark = typeof window !== 'undefined' ? useMediaQuery('(prefers-color-scheme: dark)') : ref(false);

    const isDark = computed(() => appearance.value === 'dark' || (appearance.value === 'system' && isSystemDark.value));

    const currentTheme = computed(() => (isDark.value ? theme.value?.dark : theme.value?.light) ?? {});
    const hasAlternativeFonts = computed(() => ['--font-header-alternative', '--font-body-alternative'].some((key) => {
        const value = currentTheme.value[key];

        return Boolean(value && value !== NO_ALTERNATIVE_FONT);
    }));

    const mergedThemeStyle = computed<Record<string, string>>(() => {
        const activeTheme = { ...currentTheme.value };
        const style: Record<string, string> = {};

        for (const { role, fontKey, scaleKey, alternativeScaleKey } of ALTERNATIVE_FONT_ROLES) {
            const alternativeFont = activeTheme[fontKey];

            if (useAlternativeFonts.value && alternativeFont && alternativeFont !== NO_ALTERNATIVE_FONT) {
                activeTheme[`--font-${role}`] = alternativeFont;

                if (activeTheme[alternativeScaleKey] !== undefined) {
                    activeTheme[scaleKey] = activeTheme[alternativeScaleKey];
                }
            }

            delete activeTheme[fontKey];
            delete activeTheme[alternativeScaleKey];
        }

        processDefinedKeys(activeTheme, style);
        processMissingScales(activeTheme, style);
        processFontAndColorKeys(activeTheme, style);

        return style;
    });

    return { isDark, mergedThemeStyle, hasAlternativeFonts };
}
