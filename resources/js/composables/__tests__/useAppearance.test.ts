import { beforeEach, describe, expect, it, vi } from 'vitest';
import { initializeTheme } from '../useAppearance';

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
    });

    it('initializeTheme adds event listener and returns cleanup function', () => {
        const cleanup = initializeTheme();

        expect(addEventListenerSpy).toHaveBeenCalledWith('change', expect.any(Function));

        if (cleanup) cleanup();
        expect(removeEventListenerSpy).toHaveBeenCalledWith('change', expect.any(Function));
    });
});
