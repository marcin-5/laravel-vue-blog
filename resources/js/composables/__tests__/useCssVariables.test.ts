import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent, nextTick } from 'vue';
import { useCssVariables } from '../useCssVariables';

describe('useCssVariables', () => {
    const variableNames = ['--test-var'];

    beforeEach(() => {
        vi.restoreAllMocks();
        document.documentElement.className = '';
        // Mock getComputedStyle
        vi.spyOn(window, 'getComputedStyle').mockReturnValue({
            getPropertyValue: (name: string) => {
                if (name === '--test-var') return 'test-value';
                return '';
            },
        } as any);
    });

    it('initializes and updates variables on mount', async () => {
        const TestComponent = defineComponent({
            setup() {
                const { variables } = useCssVariables(variableNames);
                return { variables };
            },
            template: '<div></div>',
        });

        const wrapper = mount(TestComponent);
        await nextTick();

        expect(wrapper.vm.variables['--test-var']).toBe('test-value');
    });

    it('disconnects MutationObserver on unmount', async () => {
        const disconnectSpy = vi.fn();

        // Mock MutationObserver
        const MutationObserverMock = vi.fn().mockImplementation(function (this: any) {
            this.observe = vi.fn();
            this.disconnect = disconnectSpy;
        });
        vi.stubGlobal('MutationObserver', MutationObserverMock);

        const TestComponent = defineComponent({
            setup() {
                useCssVariables(variableNames);
                return {};
            },
            template: '<div></div>',
        });

        const wrapper = mount(TestComponent);
        await nextTick();

        wrapper.unmount();

        expect(disconnectSpy).toHaveBeenCalled();
    });
});
