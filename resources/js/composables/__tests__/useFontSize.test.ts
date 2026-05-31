import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent } from 'vue';

const localStorageMock = {
    getItem: vi.fn(),
    setItem: vi.fn(),
    clear: vi.fn(),
    removeItem: vi.fn(),
};

vi.stubGlobal('localStorage', localStorageMock);

describe('useFontSize', () => {
    beforeEach(async () => {
        vi.resetModules();
        vi.clearAllMocks();
        if (typeof document !== 'undefined') {
            document.documentElement.style.fontSize = '';
        }
    });

    it('applyFontSize sets the font size on documentElement', async () => {
        const { applyFontSize } = await import('../useFontSize');
        applyFontSize(120);
        expect(document.documentElement.style.fontSize).toBe('120%');
    });

    it('initializeFontSize loads from localStorage', async () => {
        const { initializeFontSize } = await import('../useFontSize');
        vi.mocked(localStorageMock.getItem).mockReturnValue('110');
        initializeFontSize();
        expect(document.documentElement.style.fontSize).toBe('110%');
    });

    it('useFontSize returns ref and update function', async () => {
        const { useFontSize } = await import('../useFontSize');

        let fontSizeResult: any;
        let updateFontSizeResult: any;

        const TestComponent = defineComponent({
            setup() {
                const { fontSize, updateFontSize } = useFontSize();
                fontSizeResult = fontSize;
                updateFontSizeResult = updateFontSize;
                return {};
            },
            template: '<div></div>',
        });

        mount(TestComponent);

        updateFontSizeResult([115]);
        expect(fontSizeResult.value).toEqual([115]);
        expect(document.documentElement.style.fontSize).toBe('115%');
    });
});
