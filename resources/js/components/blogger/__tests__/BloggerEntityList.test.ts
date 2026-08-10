import BloggerEntityList from '@/components/blogger/BloggerEntityList.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { h } from 'vue';

vi.mock('@/components/ui/button', () => ({
    Button: { name: 'Button', inheritAttrs: false, template: '<button v-bind="$attrs"><slot /></button>' },
}));

const emptyState = {
    emptyText: 'No items yet',
    emptyCta: 'Create item',
    limitReachedHint: 'Creation limit reached',
};

describe('BloggerEntityList', () => {
    it('renders each item through the typed default slot using item ids as keys', () => {
        const items = [
            { id: 1, name: 'First' },
            { id: 2, name: 'Second' },
        ];
        const wrapper = mount(BloggerEntityList, {
            props: { canCreate: true, emptyState, items },
            slots: {
                default: ({ item }) => h('div', { class: 'entity-item' }, item.id),
            },
        });

        expect(wrapper.findAll('.entity-item')).toHaveLength(items.length);
        expect(wrapper.text()).toContain('1');
        expect(wrapper.text()).toContain('2');
        expect(wrapper.findComponent({ name: 'BloggerEmptyState' }).exists()).toBe(false);
    });

    it('renders the shared empty state and forwards its create event', async () => {
        const wrapper = mount(BloggerEntityList, { props: { canCreate: true, emptyState, items: [] } });

        expect(wrapper.text()).toContain(emptyState.emptyText);

        await wrapper.find('button').trigger('click');

        expect(wrapper.emitted('create')).toHaveLength(1);
    });

    it('keeps the empty-state CTA disabled when creation is unavailable', () => {
        const wrapper = mount(BloggerEntityList, { props: { canCreate: false, emptyState, items: [] } });

        expect(wrapper.find('button').attributes('disabled')).toBeDefined();
    });
});
