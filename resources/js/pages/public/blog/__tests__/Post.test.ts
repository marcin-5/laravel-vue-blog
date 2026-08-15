import type { Blog, BlogChrome, PostDetails, PostListing } from '@/types/blog.types';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import Post from '../Post.vue';

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

vi.mock('@/components/blog/PostHeader.vue', () => ({
    default: { name: 'PostHeader', props: ['post', 'viewStats', 'locale', 'seo'], template: '<header class="post-header" />' },
}));

vi.mock('@/components/blog/PostContent.vue', () => ({
    default: { name: 'PostContent', props: ['author', 'content'], template: '<div class="post-content" v-html="content" />' },
}));

vi.mock('@/components/blog/PostExtensions.vue', () => ({
    default: { name: 'PostExtensions', props: ['extensions'], template: '<div class="post-extensions" />' },
}));

vi.mock('@/components/blog/PostRelatedPosts.vue', () => ({
    default: { name: 'PostRelatedPosts', props: ['items'], template: '<div class="related-posts" />' },
}));

vi.mock('@/components/blog/PostExternalLinks.vue', () => ({
    default: { name: 'PostExternalLinks', props: ['items'], template: '<div class="external-links" />' },
}));

vi.mock('@/components/blog/BlogPostNav.vue', () => ({
    default: { name: 'BlogPostNav', props: ['navigation', 'activeTag'], template: '<div class="post-nav" />' },
}));

vi.mock('@/components/blog/PostBackLink.vue', () => ({
    default: { name: 'PostBackLink', props: ['landingUrl'], template: '<div class="post-back-link" />' },
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
};

const chrome: BlogChrome = {
    locale: 'pl',
    sidebar: -30,
    sidebarPosition: 'left',
    navigation: { landingUrl: 'https://blog.test/landing', breadcrumbs: [] },
    footerHtml: null,
};

const listing: PostListing = {
    posts: [{ id: 1, title: 'Post', slug: 'post' }],
    pagination: null,
    activeTag: null,
    allTags: [],
};

const post: PostDetails = {
    id: 1,
    title: 'Post title',
    slug: 'post-title',
    author: 'Author',
    author_email: 'author@test',
    contentHtml: '<p>Body</p>',
    summaryHtml: '<p>Summary</p>',
    visibility: 'public',
};

const mountPost = (overrides: Partial<{ post: PostDetails; chrome: BlogChrome }> = {}) =>
    mount(Post, {
        props: { blog, chrome, listing, post, viewStats: null, ...overrides },
    });

describe('Post.vue', () => {
    it('renders the header and the content blocks', () => {
        const wrapper = mountPost();

        expect(wrapper.findComponent({ name: 'PostHeader' }).props('post')).toEqual(post);
        expect(wrapper.findAllComponents({ name: 'PostContent' })).toHaveLength(2);
        expect(wrapper.findComponent({ name: 'PostExtensions' }).exists()).toBe(true);
    });

    it('omits the summary block when the post has none', () => {
        const wrapper = mountPost({ post: { ...post, summaryHtml: null } });

        expect(wrapper.findAllComponents({ name: 'PostContent' })).toHaveLength(1);
    });

    it('passes the listing to the shell and renders the post navigation for a listed post', () => {
        const wrapper = mountPost();

        expect(wrapper.findComponent({ name: 'PublicBlogShell' }).props('listing')).toEqual(listing);
        expect(wrapper.findComponent({ name: 'BlogPostNav' }).exists()).toBe(true);
        expect(wrapper.findComponent({ name: 'PostBackLink' }).exists()).toBe(false);
    });

    it('hides the listing and shows the back link for an unlisted post', () => {
        const wrapper = mountPost({ post: { ...post, visibility: 'unlisted' } });

        expect(wrapper.findComponent({ name: 'PublicBlogShell' }).props('listing')).toBeNull();
        expect(wrapper.findComponent({ name: 'BlogPostNav' }).exists()).toBe(false);
        expect(wrapper.findComponent({ name: 'PostBackLink' }).props('landingUrl')).toBe('https://blog.test/landing');
    });

    it('falls back to the blog url when the navigation has no landing url', () => {
        const wrapper = mountPost({
            post: { ...post, visibility: 'unlisted' },
            chrome: { ...chrome, navigation: undefined },
        });

        expect(wrapper.findComponent({ name: 'PostBackLink' }).props('landingUrl')).toBe(blog.url);
    });

    it('spaces the posts list only when there is no sidebar', () => {
        expect(mountPost().findComponent({ name: 'PublicBlogShell' }).props('contentSpacingClass')).toBe('');

        const withoutSidebar = mountPost({ chrome: { ...chrome, sidebar: 0 } });
        expect(withoutSidebar.findComponent({ name: 'PublicBlogShell' }).props('contentSpacingClass')).toBe('mt-6');
    });
});
