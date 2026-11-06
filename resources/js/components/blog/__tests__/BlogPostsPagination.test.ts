import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import BlogPostsPagination from '../BlogPostsPagination.vue';

vi.mock('@inertiajs/vue3', async () => {
    const { defineComponent, h } = await import('vue');

    return {
        Link: defineComponent({
            name: 'InertiaLink',
            props: { href: { type: String, required: true } },
            setup(props, { slots }) {
                return () => h('a', { href: props.href }, slots.default?.());
            },
        }),
    };
});

vi.mock('vue-i18n', () => ({
    useI18n: () => ({
        t: (key: string) =>
            ({
                'blog.pagination.aria': 'Paginacja',
                'blog.pagination.previous': 'Wstecz',
                'blog.pagination.next': 'Dalej',
            })[key] ?? key,
    }),
}));

const links = [
    { url: null, label: '« Previous', active: false },
    { url: '/blogs/sample-blog?page=1', label: '1', active: true },
    { url: '/blogs/sample-blog?page=2', label: '2', active: false },
    { url: '/blogs/sample-blog?page=2', label: 'Next &raquo;', active: false },
];

function mountPagination(paginationLinks = links) {
    return mount(BlogPostsPagination, { props: { links: paginationLinks } });
}

describe('BlogPostsPagination', () => {
    it('renders a labelled navigation landmark', () => {
        const wrapper = mountPagination();

        expect(wrapper.find('nav').attributes('aria-label')).toBe('Paginacja');
    });

    it('translates previous and next labels and strips arrow decorations', () => {
        const wrapper = mountPagination();
        const texts = wrapper.findAll('a').map((link) => link.text());

        expect(texts).toEqual(['Wstecz', '1', '2', 'Dalej']);
    });

    it('keeps numeric labels unchanged', () => {
        const wrapper = mountPagination([{ url: '/blogs/sample-blog?page=5', label: '5', active: false }]);

        expect(wrapper.find('a').text()).toBe('5');
    });

    it('marks the active page with the active classes', () => {
        const wrapper = mountPagination();
        const activeLink = wrapper.findAll('a')[1];

        expect(activeLink.classes()).toContain('border-foreground');
        expect(activeLink.classes()).not.toContain('border-border');
    });

    it('disables links without a url', () => {
        const wrapper = mountPagination();
        const previousLink = wrapper.findAll('a')[0];
        const enabledLink = wrapper.findAll('a')[2];

        expect(previousLink.classes()).toContain('pointer-events-none');
        expect(previousLink.classes()).not.toContain('hover:bg-secondary');
        expect(enabledLink.classes()).toContain('hover:bg-secondary');
        expect(enabledLink.classes()).not.toContain('pointer-events-none');
    });

    it('points enabled links to their urls', () => {
        const wrapper = mountPagination();

        expect(wrapper.findAll('a')[2].attributes('href')).toBe('/blogs/sample-blog?page=2');
    });

    it('renders an empty href for links without a url', () => {
        const wrapper = mountPagination();

        expect(wrapper.findAll('a')[0].attributes('href')).toBe('');
    });
});
