import { useAppearance } from '@/composables/useAppearance';
import { useMediaQuery } from '@vueuse/core';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { computed, ref } from 'vue';
import { useBlogTheme } from '../useBlogTheme';

vi.mock('@/composables/useAppearance', () => ({
    useAppearance: vi.fn(),
}));

vi.mock('@vueuse/core', () => ({
    useMediaQuery: vi.fn(),
}));

describe('useBlogTheme', () => {
    const appearance = ref('light');
    const isSystemDark = ref(false);

    beforeEach(() => {
        vi.clearAllMocks();
        vi.mocked(useAppearance).mockReturnValue({ appearance } as any);
        vi.mocked(useMediaQuery).mockReturnValue(isSystemDark as any);
    });

    it('determines isDark correctly based on appearance and system settings', () => {
        const theme = computed(() => ({ light: {}, dark: {} }));
        const { isDark } = useBlogTheme(theme);

        appearance.value = 'light';
        expect(isDark.value).toBe(false);

        appearance.value = 'dark';
        expect(isDark.value).toBe(true);

        appearance.value = 'system';
        isSystemDark.value = false;
        expect(isDark.value).toBe(false);

        isSystemDark.value = true;
        expect(isDark.value).toBe(true);
    });

    it('merges theme styles correctly for light mode', () => {
        const theme = computed<any>(() => ({
            light: {
                '--header-scale': '1.2',
                '--font-header': 'var(--font-inter)',
                '--color-primary': '#ff0000',
            },
            dark: {
                '--header-scale': '1.5',
            },
        }));

        appearance.value = 'light';
        const { mergedThemeStyle } = useBlogTheme(theme);

        const style = mergedThemeStyle.value;

        // Scale and special mapping
        expect(style['--header-scale']).toBe('1.2');
        expect(style['--blog-header-scale']).toBe('1.2');

        // Font mapping
        expect(style['--blog-header-font']).toBe('var(--font-inter)');

        // Color mapping
        expect(style['--color-primary']).toBe('#ff0000');

        // Defaults for missing scales
        expect(style['--body-scale']).toBe('1');
    });

    it('applies font size corrections', () => {
        const theme = computed<any>(() => ({
            light: {
                '--header-scale': '1.0',
                '--font-header': 'var(--font-manuale)', // Correction is 1.1 in fonts.ts
            },
        }));

        appearance.value = 'light';
        const { mergedThemeStyle } = useBlogTheme(theme);

        expect(mergedThemeStyle.value['--header-scale']).toBe('1.1');
    });

    it('applies font size corrections to active alternative fonts', () => {
        const theme = computed(() => ({
            light: {
                '--header-scale': '1.0',
                '--body-scale': '1.2',
                '--font-header': 'var(--font-inter)',
                '--font-body': 'var(--font-inter)',
                '--font-header-alternative': 'var(--font-manuale)',
                '--font-body-alternative': 'var(--font-manuale)',
                '--header-scale-alternative': '1.3',
                '--body-scale-alternative': '0.9',
            },
        }));
        const useAlternativeFonts = ref(false);
        const { mergedThemeStyle } = useBlogTheme(theme, useAlternativeFonts);

        expect(mergedThemeStyle.value['--header-scale']).toBe('1');
        expect(mergedThemeStyle.value['--body-scale']).toBe('1.2');

        useAlternativeFonts.value = true;

        expect(Number(mergedThemeStyle.value['--header-scale'])).toBeCloseTo(1.43);
        expect(Number(mergedThemeStyle.value['--body-scale'])).toBeCloseTo(0.99);
        expect(Number(mergedThemeStyle.value['--blog-header-scale'])).toBeCloseTo(1.43);
        expect(Number(mergedThemeStyle.value['--blog-body-scale'])).toBeCloseTo(0.99);
    });

    it('uses default scales for alternative fonts when legacy themes have no alternative scales', () => {
        const theme = computed(() => ({
            light: {
                '--header-scale': '1.2',
                '--body-scale': '0.9',
                '--font-header': 'var(--font-inter)',
                '--font-body': 'var(--font-inter)',
                '--font-header-alternative': 'var(--font-inter)',
                '--font-body-alternative': 'var(--font-inter)',
            },
        }));
        const { mergedThemeStyle } = useBlogTheme(theme, ref(true));

        expect(mergedThemeStyle.value['--header-scale']).toBe('1.2');
        expect(mergedThemeStyle.value['--body-scale']).toBe('0.9');
    });

    it('applies font weight corrections', () => {
        const theme = computed<any>(() => ({
            light: {
                '--font-header': 'var(--font-darker-grotesque)', // Weight correction is 500
            },
        }));

        appearance.value = 'light';
        const { mergedThemeStyle } = useBlogTheme(theme);

        expect(mergedThemeStyle.value['--blog-header-weight']).toBe('500');
    });

    it('uses configured alternative fonts only when the font set is enabled', () => {
        appearance.value = 'light';
        const theme = computed(() => ({
            light: {
                '--font-header': 'var(--font-inter)',
                '--font-body': 'var(--font-manuale)',
                '--font-header-alternative': 'var(--font-bitter)',
                '--font-body-alternative': '__no_alternative_font__',
            },
            dark: {
                '--font-header': 'var(--font-inter)',
                '--font-header-alternative': 'var(--font-vollkorn)',
            },
        }));
        const useAlternativeFonts = ref(false);
        const { mergedThemeStyle, hasAlternativeFonts } = useBlogTheme(theme, useAlternativeFonts);

        expect(hasAlternativeFonts.value).toBe(true);
        expect(mergedThemeStyle.value['--blog-header-font']).toBe('var(--font-inter)');
        expect(mergedThemeStyle.value['--blog-body-font']).toBe('var(--font-manuale)');
        expect(mergedThemeStyle.value['--blog-header-alternative-font']).toBeUndefined();
        expect(mergedThemeStyle.value['--blog-body-alternative-font']).toBeUndefined();

        useAlternativeFonts.value = true;

        expect(mergedThemeStyle.value['--blog-header-font']).toBe('var(--font-bitter)');
        expect(mergedThemeStyle.value['--blog-body-font']).toBe('var(--font-manuale)');

        appearance.value = 'dark';

        expect(mergedThemeStyle.value['--blog-header-font']).toBe('var(--font-vollkorn)');
        expect(mergedThemeStyle.value['--blog-body-font']).toBeUndefined();
    });

    it('does not expose alternatives configured as none', () => {
        appearance.value = 'light';
        const theme = computed(() => ({
            light: {
                '--font-header-alternative': '__no_alternative_font__',
                '--font-body-alternative': '__no_alternative_font__',
            },
        }));
        const { mergedThemeStyle, hasAlternativeFonts } = useBlogTheme(theme, ref(true));

        expect(hasAlternativeFonts.value).toBe(false);
        expect(mergedThemeStyle.value['--blog-header-alternative-font']).toBeUndefined();
        expect(mergedThemeStyle.value['--blog-body-alternative-font']).toBeUndefined();
    });
});
