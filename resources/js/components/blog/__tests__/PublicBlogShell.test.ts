import type { Blog, BlogChrome, PostListing } from '@/types/blog.types';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import PublicBlogShell from '../PublicBlogShell.vue';

vi.mock('@/components/blog/BlogLayout.vue', () => ({
    default: {
        name: 'BlogLayout',
        props: ['theme', 'sidebar', 'isPublic', 'maxWidthClass'],
        template: `
            <div class="blog-layout">
                <slot name="top-divider" />
                <slot name="header" />
                <slot name="content" />
                <slot name="middle-divider" />
                <slot name="breadcrumbs" />
                <slot name="sidebar-content" />
                <slot name="navigation" />
                <slot name="footer" />
            </div>
        `,
    },
}));

vi.mock('@/components/blog/BlogBreadcrumbs.vue', () => ({
    default: { name: 'BlogBreadcrumbs', props: ['breadcrumbs'], template: '<nav class="breadcrumbs" />' },
}));

vi.mock('@/components/blog/BlogFooter.vue', () => ({
    default: { name: 'BlogFooter', props: ['html'], template: '<footer class="blog-footer" />' },
}));

vi.mock('@/components/blog/BlogPostsList.vue', () => ({
    default: {
        name: 'BlogPostsList',
        props: ['posts', 'blogSlug', 'mainDomain', 'blogId', 'pagination', 'isGroup', 'activeTag', 'allTags'],
        template: '<div class="posts-list" />',
    },
}));

vi.mock('@/components/blog/BlogPostNav.vue', () => ({
    default: { name: 'BlogPostNav', props: ['navigation', 'activeTag'], template: '<div class="post-nav" />' },
}));

vi.mock('@/components/blog/BorderDivider.vue', () => ({
    default: { name: 'BorderDivider', template: '<hr class="divider" />' },
}));

const blog: Blog = {
    id: 7,
    name: 'Blog',
    slug: 'blog',
    url: 'https://blog.test',
    main_domain: 'test',
    theme: { light: { '--blog-bg': '#fff' } },
};

const chrome: BlogChrome = {
    locale: 'pl',
    sidebar: -30,
    sidebarPosition: 'left',
    navigation: {
        landingUrl: 'https://blog.test',
        breadcrumbs: [{ label: 'Home', url: 'https://test' }],
    },
    footerHtml: '<p>Footer</p>',
};

const listing: PostListing = {
    posts: [{ id: 1, title: 'Post', slug: 'post' }],
    pagination: null,
    activeTag: { id: 3, name: 'Laravel', slug: 'laravel' },
    allTags: [],
};

const mountShell = (props: Partial<InstanceType<typeof PublicBlogShell>['$props']> = {}) =>
    mount(PublicBlogShell, {
        props: { blog, chrome, ...props },
    });

describe('PublicBlogShell.vue', () => {
    it('forwards the blog theme and the chrome sidebar to the layout', () => {
        const layout = mountShell().findComponent({ name: 'BlogLayout' });

        expect(layout.props('theme')).toEqual(blog.theme);
        expect(layout.props('sidebar')).toBe(-30);
        expect(layout.props('isPublic')).toBe(true);
    });

    it('renders the breadcrumbs coming from the chrome navigation', () => {
        const wrapper = mountShell();

        expect(wrapper.findComponent({ name: 'BlogBreadcrumbs' }).props('breadcrumbs')).toEqual(chrome.navigation?.breadcrumbs);
    });

    it('falls back to an empty breadcrumb list when navigation is missing', () => {
        const wrapper = mountShell({ chrome: { ...chrome, navigation: undefined } });

        expect(wrapper.findComponent({ name: 'BlogBreadcrumbs' }).props('breadcrumbs')).toEqual([]);
    });

    it('renders the footer only when the footer html has content', () => {
        expect(mountShell().findComponent({ name: 'BlogFooter' }).exists()).toBe(true);
        expect(mountShell({ chrome: { ...chrome, footerHtml: '   ' } }).findComponent({ name: 'BlogFooter' }).exists()).toBe(false);
        expect(mountShell({ chrome: { ...chrome, footerHtml: null } }).findComponent({ name: 'BlogFooter' }).exists()).toBe(false);
    });

    it('builds the posts list and the post nav from the listing', () => {
        const wrapper = mountShell({ listing, contentSpacingClass: 'mt-6' });

        const postsList = wrapper.findComponent({ name: 'BlogPostsList' });
        expect(postsList.props('posts')).toEqual(listing.posts);
        expect(postsList.props('activeTag')).toEqual(listing.activeTag);
        expect(postsList.props('allTags')).toEqual(listing.allTags);
        expect(postsList.props('blogId')).toBe(blog.id);
        expect(postsList.props('blogSlug')).toBe(blog.slug);
        expect(postsList.props('mainDomain')).toBe(blog.main_domain);
        expect(postsList.classes()).toContain('mt-6');

        const postNav = wrapper.findComponent({ name: 'BlogPostNav' });
        expect(postNav.props('navigation')).toEqual(chrome.navigation);
        expect(postNav.props('activeTag')).toEqual(listing.activeTag);
    });

    it('renders neither the posts list nor the post nav without a listing', () => {
        const wrapper = mountShell();

        expect(wrapper.findComponent({ name: 'BlogPostsList' }).exists()).toBe(false);
        expect(wrapper.findComponent({ name: 'BlogPostNav' }).exists()).toBe(false);
    });

    it('lets pages replace the default sidebar content and navigation', () => {
        const overridden = mount(PublicBlogShell, {
            props: { blog, chrome, listing },
            slots: {
                header: '<h1 class="page-header">Header</h1>',
                content: '<div class="page-content">Content</div>',
                navigation: '<div class="back-link">Back</div>',
                'sidebar-content': '<div class="custom-sidebar">Sidebar</div>',
            },
        });

        expect(overridden.find('.page-header').exists()).toBe(true);
        expect(overridden.find('.page-content').exists()).toBe(true);
        expect(overridden.find('.back-link').exists()).toBe(true);
        expect(overridden.find('.custom-sidebar').exists()).toBe(true);
        expect(overridden.findComponent({ name: 'BlogPostsList' }).exists()).toBe(false);
        expect(overridden.findComponent({ name: 'BlogPostNav' }).exists()).toBe(false);
    });
});
