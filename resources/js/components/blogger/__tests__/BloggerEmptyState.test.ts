import BloggerEmptyState from '@/components/blogger/BloggerEmptyState.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@/components/ui/button', () => ({
    Button: { name: 'Button', inheritAttrs: false, template: '<button v-bind="$attrs"><slot /></button>' },
}));

const labels = {
    emptyText: 'No items yet',
    emptyCta: 'Create item',
    limitReachedHint: 'Creation limit reached',
};

describe('BloggerEmptyState', () => {
    it('renders the provided labels and emits create when the CTA is clicked', async () => {
        const wrapper = mount(BloggerEmptyState, { props: { canCreate: true, ...labels } });

        expect(wrapper.text()).toContain(labels.emptyText);
        expect(wrapper.text()).toContain(labels.emptyCta);

        await wrapper.find('button').trigger('click');

        expect(wrapper.emitted('create')).toHaveLength(1);
    });

    it('disables the CTA and shows the limit hint when creation is unavailable', () => {
        const wrapper = mount(BloggerEmptyState, { props: { canCreate: false, ...labels } });
        const button = wrapper.find('button');

        expect(button.attributes('disabled')).toBeDefined();
        expect(button.attributes('title')).toBeUndefined();
        expect(button.element.parentElement?.getAttribute('title')).toBe(labels.limitReachedHint);
    });
});
