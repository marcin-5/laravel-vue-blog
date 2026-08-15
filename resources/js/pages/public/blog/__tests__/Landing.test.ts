import type { Blog, BlogChrome, PostListing } from '@/types/blog.types';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import Landing from '../Landing.vue';

vi.mock('@/components/blog/PublicBlogShell.vue', () => ({
    default: {
        name: 'PublicBlogShell',
        props: ['blog', 'chrome', 'listing', 'contentSpacingClass', 'middleDividerClass'],
        template: `
            <div class="shell">
                <slot name="header" />
                <slot name="content" />
                <slot name="sidebar-content" />
                <slot name="navigation" />
            </div>
        `,
    },
}));

vi.mock('@/components/blog/BlogHeader.vue', () => ({
    default: { name: 'BlogHeader', props: ['blog', 'viewStats'], template: '<header class="blog-header" />' },
}));

vi.mock('@/components/blog/ScrollToPostsLink.vue', () => ({
    default: { name: 'ScrollToPostsLink', template: '<a class="scroll-to-posts" />' },
}));

const blog: Blog = {
    id: 7,
    name: 'Blog',
    slug: 'blog',
    url: 'https://blog.test',
    main_domain: 'test',
};

const chrome: BlogChrome = {
    locale: 'pl',
    sidebar: -30,
    sidebarPosition: 'left',
    navigation: { landingUrl: 'https://blog.test', breadcrumbs: [] },
    footerHtml: '<p>Footer</p>',
};

const listing: PostListing = {
    posts: [{ id: 1, title: 'Post', slug: 'post' }],
    pagination: null,
    activeTag: null,
    allTags: [],
};

const mountLanding = (landingHtml = '<p>Hello</p>') =>
    mount(Landing, {
        props: { blog, chrome, listing, landingHtml, viewStats: null },
    });

describe('Landing.vue', () => {
    it('forwards the page data to the shell', () => {
        const shell = mountLanding().findComponent({ name: 'PublicBlogShell' });

        expect(shell.props('blog')).toEqual(blog);
        expect(shell.props('chrome')).toEqual(chrome);
        expect(shell.props('listing')).toEqual(listing);
    });

    it('renders the header with the blog and hides the scroll link on wide screens with a sidebar', () => {
        const wrapper = mountLanding();

        expect(wrapper.findComponent({ name: 'BlogHeader' }).props('blog')).toEqual(blog);
        expect(wrapper.findComponent({ name: 'ScrollToPostsLink' }).classes()).toContain('xl:hidden');
    });

    it('keeps the scroll link visible when there is no sidebar', () => {
        const wrapper = mount(Landing, {
            props: { blog, chrome: { ...chrome, sidebar: 0 }, listing, landingHtml: '' },
        });

        expect(wrapper.findComponent({ name: 'ScrollToPostsLink' }).classes()).not.toContain('xl:hidden');
    });

    it('renders the landing content only when it is not blank and spaces the posts list accordingly', () => {
        const withContent = mountLanding();
        expect(withContent.find('.prose').html()).toContain('Hello');
        expect(withContent.findComponent({ name: 'PublicBlogShell' }).props('contentSpacingClass')).toBe('mt-6');

        const blank = mountLanding('   ');
        expect(blank.find('.prose').exists()).toBe(false);
        expect(blank.findComponent({ name: 'PublicBlogShell' }).props('contentSpacingClass')).toBe('');
    });
});
