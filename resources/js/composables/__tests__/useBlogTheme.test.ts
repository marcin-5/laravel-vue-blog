import { useAppearance } from '@/composables/useAppearance';
import { useMediaQuery } from '@vueuse/core';
import { computed, ref } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';
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
            }
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
                '--font-header': 'var(--font-yrsa)', // Correction is 1.1 in fonts.ts
            }
        }));

        appearance.value = 'light';
        const { mergedThemeStyle } = useBlogTheme(theme);

        expect(mergedThemeStyle.value['--header-scale']).toBe('1.1');
    });

    it('applies font weight corrections', () => {
        const theme = computed<any>(() => ({
            light: {
                '--font-header': 'var(--font-darker-grotesque)', // Weight correction is 500
            }
        }));

        appearance.value = 'light';
        const { mergedThemeStyle } = useBlogTheme(theme);

        expect(mergedThemeStyle.value['--blog-header-weight']).toBe('500');
    });
});
