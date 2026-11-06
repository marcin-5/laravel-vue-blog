import type { PostItem, Tag } from '@/types/blog.types';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useBlogPostsListLinks, type ActiveTag, type BlogPostsListLinkProps } from '../useBlogPostsListLinks';

const tagAlpha: Tag = { id: 1, blog_id: 7, name: 'Alpha', slug: 'alpha' };
const tagBeta: Tag = { id: 2, blog_id: 7, name: 'Beta', slug: 'beta' };
const post = { id: 10, slug: 'hello-world', title: 'Hello world' } as PostItem;

const baseProps: BlogPostsListLinkProps = {
    blogSlug: 'sample-blog',
    mainDomain: 'example.test',
    blogId: 7,
};

function routeStub(name: string, params: Record<string, unknown> = {}): string {
    switch (name) {
        case 'blog.public.landing':
            return `/blogs/${params.blog}`;
        case 'blog.public.tag':
            return `/blogs/${params.blog}/tags/${params.tag}`;
        case 'blog.public.post':
            return `/blogs/${params.blog}/posts/${params.postSlug}`;
        case 'group.post':
            return `/groups/${params.group}/posts/${params.postSlug}`;
        case 'newsletter.index':
            return `/newsletter?blog_id=${params.blog_id}`;
        default:
            return `/unknown/${name}`;
    }
}

function useLinks(props: Partial<BlogPostsListLinkProps> = {}) {
    return useBlogPostsListLinks({ ...baseProps, ...props });
}

describe('useBlogPostsListLinks', () => {
    beforeEach(() => {
        vi.stubGlobal('route', vi.fn(routeStub));
        window.history.replaceState({}, '', '/blogs/sample-blog?filter=recent&page=3&tags[]=old&operator=OR');
    });

    it('uses the group anchor only for group lists', () => {
        expect(useLinks().anchorId.value).toBe('#posts-list');
        expect(useLinks({ isGroup: true }).anchorId.value).toBe('#group-posts-list');
    });

    it('builds the post href for a blog without tag filters', () => {
        const { getPostHref } = useLinks();

        expect(getPostHref(post)).toBe('/blogs/sample-blog/posts/hello-world');
        expect(route).toHaveBeenCalledWith('blog.public.post', {
            blog: 'sample-blog',
            mainDomain: 'example.test',
            postSlug: 'hello-world',
        });
    });

    it('builds the post href for a group using the group route', () => {
        const { getPostHref } = useLinks({ isGroup: true });

        expect(getPostHref(post)).toBe('/groups/sample-blog/posts/hello-world');
        expect(route).toHaveBeenCalledWith('group.post', { group: 'sample-blog', postSlug: 'hello-world' });
    });

    it('adds the single active tag to the post route params', () => {
        const { getPostHref } = useLinks({ activeTag: tagAlpha });

        getPostHref(post);

        expect(route).toHaveBeenCalledWith('blog.public.post', expect.objectContaining({ tag: 'alpha' }));
    });

    it('adds all selected tags and operator to the post route params in multi-tag mode', () => {
        const activeTags: ActiveTag[] = [tagAlpha, tagBeta];
        const { getPostHref } = useLinks({ allowMultipleTagFiltering: true, activeTags, operator: 'OR' });

        getPostHref(post);

        expect(route).toHaveBeenCalledWith('blog.public.post', expect.objectContaining({ tags: ['alpha', 'beta'], operator: 'OR' }));
    });

    it('builds the tag href for a single tag filter', () => {
        const { getTagHref } = useLinks();

        expect(getTagHref(tagBeta)).toBe('/blogs/sample-blog/tags/beta#posts-list');
    });

    it('uses the group anchor for tag links in group lists', () => {
        const { getTagHref } = useLinks({ isGroup: true });

        expect(getTagHref(tagBeta)).toBe('/blogs/sample-blog/tags/beta#group-posts-list');
    });

    it('adds a tag to the selection in multi-tag mode and keeps unrelated query params', () => {
        const { getTagHref } = useLinks({ allowMultipleTagFiltering: true, activeTags: [tagAlpha], operator: 'OR' });

        expect(getTagHref(tagBeta)).toBe('/blogs/sample-blog?filter=recent&tags%5B%5D=alpha&tags%5B%5D=beta&operator=OR#posts-list');
    });

    it('removes a tag from the selection in multi-tag mode', () => {
        const { getTagHref } = useLinks({ allowMultipleTagFiltering: true, activeTags: [tagAlpha, tagBeta], operator: 'AND' });

        expect(getTagHref(tagAlpha)).toBe('/blogs/sample-blog?filter=recent&tags%5B%5D=beta&operator=AND#posts-list');
    });

    it('builds the landing href with the posts anchor in single-tag mode', () => {
        const { getBlogLandingHref } = useLinks();

        expect(getBlogLandingHref()).toBe('/blogs/sample-blog#posts-list');
    });

    it('keeps the operator but drops tags when clearing filters in multi-tag mode', () => {
        const { getBlogLandingHref } = useLinks({ allowMultipleTagFiltering: true, activeTags: [tagAlpha], operator: 'OR' });

        expect(getBlogLandingHref()).toBe('/blogs/sample-blog?filter=recent&operator=OR#posts-list');
    });

    it('excludes page, tag and operator params from the current query when building multi-tag hrefs', () => {
        const { getMultiTagFilterHref } = useLinks({ allowMultipleTagFiltering: true });

        const href = getMultiTagFilterHref(['beta'], 'AND', '#posts-list');
        const url = new URL(href, window.location.origin);

        expect(url.searchParams.get('filter')).toBe('recent');
        expect(url.searchParams.has('page')).toBe(false);
        expect(url.searchParams.getAll('tags[]')).toEqual(['beta']);
        expect(url.searchParams.get('operator')).toBe('AND');
        expect(url.hash).toBe('#posts-list');
    });

    it('builds the newsletter href from the blog id', () => {
        const { getNewsletterHref } = useLinks();

        expect(getNewsletterHref()).toBe('/newsletter?blog_id=7');
        expect(route).toHaveBeenCalledWith('newsletter.index', { blog_id: 7 });
    });

    it('disables multi-tag mode for group lists and defaults the operator to AND', () => {
        const groupLinks = useLinks({ isGroup: true, allowMultipleTagFiltering: true, activeTags: [tagAlpha] });
        const blogLinks = useLinks({ allowMultipleTagFiltering: true, activeTags: [tagAlpha] });

        expect(groupLinks.isMultiTagMode.value).toBe(false);
        expect(blogLinks.isMultiTagMode.value).toBe(true);
        expect(blogLinks.activeOperator.value).toBe('AND');
    });
});
