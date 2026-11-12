import { beforeEach, describe, expect, it } from 'vitest';
import { nextTick } from 'vue';
import { enneagramThemes, useEnneagramPreferences } from '../useEnneagramPreferences';

describe('useEnneagramPreferences', () => {
    beforeEach(() => {
        localStorage.clear();
        document.cookie = '';
    });

    it('defaults to theme-light-standard when no initialTheme or localStorage is set', () => {
        const { theme, localeLabel } = useEnneagramPreferences('pl');

        expect(theme.value).toBe('theme-light-standard');
        expect(localeLabel.value).toBe('PL');
    });

    it('uses initialTheme when provided', () => {
        const { theme } = useEnneagramPreferences('en', 'theme-dark-forest');

        expect(theme.value).toBe('theme-dark-forest');
    });

    it('falls back to localStorage theme if initialTheme is not provided', () => {
        localStorage.setItem('enneagram-test-theme', 'theme-dark-purple');

        const { theme } = useEnneagramPreferences('pl');

        expect(theme.value).toBe('theme-dark-purple');
    });

    it('updates theme, localStorage, and cookie when setTheme is called', async () => {
        const { theme, setTheme } = useEnneagramPreferences('pl');

        setTheme('theme-dark-amber');
        await nextTick();

        expect(theme.value).toBe('theme-dark-amber');
        expect(localStorage.getItem('enneagram-test-theme')).toBe('theme-dark-amber');
        expect(document.cookie).toContain('enneagram_theme=theme-dark-amber');
    });

    it('ignores invalid theme values in setTheme', async () => {
        const { theme, setTheme } = useEnneagramPreferences('pl', 'theme-light-warm');

        // @ts-expect-error test invalid theme input
        setTheme('non-existent-theme');
        await nextTick();

        expect(theme.value).toBe('theme-light-warm');
    });

    it('exposes all supported themes', () => {
        expect(enneagramThemes).toContain('theme-light-standard');
        expect(enneagramThemes).toContain('theme-dark-standard');
        expect(enneagramThemes).toContain('theme-dark-forest');
    });
});
