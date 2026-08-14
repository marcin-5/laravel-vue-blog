import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import CategoryPill from '../CategoryPill.vue';

vi.mock('@/components/ui/button', () => ({
    Button: { name: 'Button', inheritAttrs: false, template: '<button v-bind="$attrs"><slot /></button>' },
}));

describe('CategoryPill.vue', () => {
    it.each([
        [true, 'true'],
        [false, 'false'],
    ])('exposes the selected state as aria-pressed for %s', (selected, expectedPressed) => {
        const wrapper = mount(CategoryPill, {
            props: { label: 'Vue', selected },
        });

        expect(wrapper.find('button').attributes('aria-pressed')).toBe(expectedPressed);
    });

    it('renders its label and emits click', async () => {
        const wrapper = mount(CategoryPill, {
            props: { label: 'Vue', selected: false },
        });

        await wrapper.find('button').trigger('click');

        expect(wrapper.text()).toBe('Vue');
        expect(wrapper.emitted('click')).toHaveLength(1);
    });
});
