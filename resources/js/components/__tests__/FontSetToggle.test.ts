import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import FontSetToggle from '../FontSetToggle.vue';

describe('FontSetToggle.vue', () => {
    it('exposes an accessible pressed state and emits a toggle', async () => {
        const wrapper = mount(FontSetToggle, {
            props: {
                active: false,
                label: 'Toggle font set',
            },
        });

        expect(wrapper.attributes('aria-label')).toBe('Toggle font set');
        expect(wrapper.attributes('aria-pressed')).toBe('false');

        await wrapper.trigger('click');

        expect(wrapper.emitted('toggle')).toHaveLength(1);
    });
});