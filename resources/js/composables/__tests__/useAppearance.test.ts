import { beforeEach, describe, expect, it, vi } from 'vitest';

const addEventListenerSpy = vi.fn();
const removeEventListenerSpy = vi.fn();
const toggleSpy = vi.fn();

const mqMock = {
    matches: false,
    addEventListener: addEventListenerSpy,
    removeEventListener: removeEventListenerSpy,
};

// Mock window and document
vi.stubGlobal('matchMedia', vi.fn().mockReturnValue(mqMock));
vi.stubGlobal('localStorage', {
    getItem: vi.fn().mockReturnValue(null),
    setItem: vi.fn(),
});
vi.stubGlobal('document', {
    documentElement: {
        classList: {
            toggle: toggleSpy,
        },
    },
    cookie: '',
});

import { initializeTheme } from '../useAppearance';

describe('useAppearance', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('initializeTheme adds event listener and returns cleanup function', () => {
        const cleanup = initializeTheme();

        expect(addEventListenerSpy).toHaveBeenCalledWith('change', expect.any(Function));

        if (cleanup) cleanup();
        expect(removeEventListenerSpy).toHaveBeenCalledWith('change', expect.any(Function));
    });
});
