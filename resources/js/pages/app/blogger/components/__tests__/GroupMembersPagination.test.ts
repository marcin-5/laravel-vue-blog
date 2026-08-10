import type { Pagination } from '@/types';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import GroupMembersPagination from '../GroupMembersPagination.vue';

vi.mock('vue-i18n', () => ({
    useI18n: () => ({
        t: (key: string) => key,
    }),
}));

const pagination: Pagination = {
    links: [
        { url: null, label: '&laquo; Previous', active: false },
        { url: '/groups/members?page=2', label: '2', active: true },
        { url: '/groups/members?page=3', label: 'Next &raquo;', active: false },
    ],
    prevUrl: null,
    nextUrl: '/groups/members?page=3',
    current_page: 2,
    last_page: 3,
    total: 30,
};

function mountPagination(currentPagination?: Pagination) {
    return mount(GroupMembersPagination, {
        props: { pagination: currentPagination },
        global: {
            stubs: {
                Button: { template: '<button v-bind="$attrs"><slot /></button>' },
            },
        },
    });
}

describe('GroupMembersPagination.vue', () => {
    it('translates labels, disables links without URLs, and emits valid visits', async () => {
        const wrapper = mountPagination(pagination);
        const buttons = wrapper.findAll('button');

        expect(buttons[0].text()).toBe('pagination.previous');
        expect(buttons[0].attributes('disabled')).toBeDefined();
        expect(buttons[2].text()).toBe('pagination.next');

        await buttons[0].trigger('click');
        await buttons[2].trigger('click');

        expect(wrapper.emitted('visitPage')).toEqual([['/groups/members?page=3']]);
    });

    it('renders nothing when pagination links are absent', () => {
        expect(mountPagination().find('button').exists()).toBe(false);
    });
});
