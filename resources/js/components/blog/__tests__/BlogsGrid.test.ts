import type { BlogItem } from '@/types/blog.types';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import BlogsGrid from '../BlogsGrid.vue';

vi.mock('../BlogCard.vue', () => ({
    default: {
        name: 'BlogCard',
        props: ['blog'],
        template: '<article>{{ blog.name }}</article>',
    },
}));

describe('BlogsGrid.vue', () => {
    const blog: BlogItem = {
        id: 1,
        name: 'Blog',
        slug: 'blog',
        url: 'https://blog.test',
        author: 'Author',
        categories: [],
    };

    it('renders every supplied blog without owning an empty state', () => {
        const wrapper = mount(BlogsGrid, {
            props: { blogs: [blog] },
        });

        expect(wrapper.findComponent({ name: 'BlogCard' }).props('blog')).toEqual(blog);
        expect(wrapper.find('div').exists()).toBe(true);
    });

    it('keeps its rendering wrapper when the supplied collection is empty', () => {
        const wrapper = mount(BlogsGrid, {
            props: { blogs: [] },
        });

        expect(wrapper.find('div').exists()).toBe(true);
        expect(wrapper.findComponent({ name: 'BlogCard' }).exists()).toBe(false);
        expect(wrapper.text()).toBe('');
    });
});
