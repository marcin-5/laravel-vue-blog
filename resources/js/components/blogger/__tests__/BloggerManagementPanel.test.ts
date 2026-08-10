import BloggerManagementPanel from '@/components/blogger/BloggerManagementPanel.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import { h } from 'vue';

describe('BloggerManagementPanel', () => {
    const items = [{ id: 1 }];

    it('opens and closes through scoped slot actions', async () => {
        const wrapper = mount(BloggerManagementPanel, {
            props: { items, canCreate: true },
            slots: {
                create: ({ isOpen, toggle }) => h('button', { 'data-test': 'toggle', onClick: toggle }, isOpen ? 'open' : 'closed'),
            },
        });

        const toggle = wrapper.get('[data-test="toggle"]');
        expect(toggle.text()).toBe('closed');

        await toggle.trigger('click');
        expect(toggle.text()).toBe('open');

        await toggle.trigger('click');
        expect(toggle.text()).toBe('closed');
    });

    it('opens from the list request action', async () => {
        const wrapper = mount(BloggerManagementPanel, {
            props: { items, canCreate: true },
            slots: {
                create: ({ isOpen }) => h('span', { 'data-test': 'state' }, String(isOpen)),
                list: ({ requestCreate }) => h('button', { onClick: requestCreate }, 'create'),
            },
        });

        await wrapper.get('button').trigger('click');

        expect(wrapper.get('[data-test="state"]').text()).toBe('true');
    });

    it('does not open when creation is unavailable', async () => {
        const wrapper = mount(BloggerManagementPanel, {
            props: { items, canCreate: false },
            slots: {
                create: ({ isOpen, open }) => h('button', { onClick: open }, String(isOpen)),
            },
        });

        await wrapper.get('button').trigger('click');

        expect(wrapper.get('button').text()).toBe('false');
    });
});
