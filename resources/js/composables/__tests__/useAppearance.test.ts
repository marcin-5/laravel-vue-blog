import { beforeEach, describe, expect, it, vi } from 'vitest';
import { initializeTheme, updateTheme, useAppearance } from '../useAppearance';

const addEventListenerSpy = vi.fn();
const removeEventListenerSpy = vi.fn();

const mqMock = {
    matches: false,
    addEventListener: addEventListenerSpy,
    removeEventListener: removeEventListenerSpy,
};

describe('useAppearance', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        window.matchMedia = vi.fn().mockReturnValue(mqMock);
        localStorage.clear();
        document.documentElement.classList.remove('dark');
        document.cookie = '';
    });

    it('initializeTheme adds event listener and returns cleanup function', () => {
        const cleanup = initializeTheme();

        expect(addEventListenerSpy).toHaveBeenCalledWith('change', expect.any(Function));

        if (cleanup) cleanup();
        expect(removeEventListenerSpy).toHaveBeenCalledWith('change', expect.any(Function));
    });

    it('updateAppearance updates appearance, localStorage, cookie and html class', () => {
        const { appearance, updateAppearance } = useAppearance();

        updateAppearance('dark');

        expect(appearance.value).toBe('dark');
        expect(localStorage.getItem('appearance')).toBe('dark');
        expect(document.cookie).toContain('appearance=dark');
        expect(document.documentElement.classList.contains('dark')).toBe(true);

        updateAppearance('light');

        expect(appearance.value).toBe('light');
        expect(localStorage.getItem('appearance')).toBe('light');
        expect(document.cookie).toContain('appearance=light');
        expect(document.documentElement.classList.contains('dark')).toBe(false);
    });

    it('initializeTheme syncs existing localStorage appearance to cookie and updates class', () => {
        localStorage.setItem('appearance', 'dark');

        initializeTheme();

        expect(document.cookie).toContain('appearance=dark');
        expect(document.documentElement.classList.contains('dark')).toBe(true);
    });

    it('updateTheme toggles dark class based on system media query when appearance is system', () => {
        window.matchMedia = vi.fn().mockReturnValue({
            matches: true,
            addEventListener: vi.fn(),
            removeEventListener: vi.fn(),
        });

        updateTheme('system');
        expect(document.documentElement.classList.contains('dark')).toBe(true);

        window.matchMedia = vi.fn().mockReturnValue({
            matches: false,
            addEventListener: vi.fn(),
            removeEventListener: vi.fn(),
        });

        updateTheme('system');
        expect(document.documentElement.classList.contains('dark')).toBe(false);
    });
});
