import { useAppearance } from '@/composables/useAppearance';
import type { BlogTheme } from '@/types/blog.types';
import { useMediaQuery } from '@vueuse/core';
import { computed, type ComputedRef } from 'vue';

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
                style[key] = value;
                if (key.startsWith('--')) {
                    style[`--color-${key.slice(2)}`] = value;
                }
            }
        }
        return style;
    });

    return { isDark, mergedThemeStyle };
}
