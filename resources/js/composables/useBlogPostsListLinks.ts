import type { PostItem, Tag } from '@/types/blog.types';
import { computed } from 'vue';

export type ActiveTag = Pick<Tag, 'id' | 'name' | 'slug'>;
export type TagOperator = 'AND' | 'OR';

export type RouteParams = {
    blog?: string;
    group?: string;
    mainDomain?: string;
    postSlug: string;
    tag?: string;
    tags?: string[];
    operator?: TagOperator;
};

export type BlogPostsListLinkProps = {
    blogSlug: string;
    mainDomain?: string;
    blogId: number;
    isGroup?: boolean;
    activeTag?: ActiveTag | null;
    activeTags?: ActiveTag[];
    operator?: TagOperator;
    allowMultipleTagFiltering?: boolean;
};

const POST_ROUTE_NAMES = {
    group: 'group.post',
    blog: 'blog.public.post',
} as const;

const ROUTE_NAMES = {
    blogLanding: 'blog.public.landing',
    blogTag: 'blog.public.tag',
    newsletter: 'newsletter.index',
} as const;

export function useBlogPostsListLinks(props: BlogPostsListLinkProps) {
    const postRouteName = computed(() => (props.isGroup ? POST_ROUTE_NAMES.group : POST_ROUTE_NAMES.blog));
    const anchorId = computed(() => (props.isGroup ? '#group-posts-list' : '#posts-list'));
    const isMultiTagMode = computed(() => !props.isGroup && props.allowMultipleTagFiltering);
    const selectedTags = computed(() => props.activeTags ?? (props.activeTag ? [props.activeTag] : []));
    const activeOperator = computed(() => props.operator ?? 'AND');

    function getPostRouteParams(post: PostItem): RouteParams {
        const params: RouteParams = props.isGroup
            ? { group: props.blogSlug, postSlug: post.slug }
            : { blog: props.blogSlug, mainDomain: props.mainDomain, postSlug: post.slug };

        if (isMultiTagMode.value && selectedTags.value.length > 0) {
            params.tags = selectedTags.value.map((tag) => tag.slug);
            params.operator = activeOperator.value;
        } else if (props.activeTag) {
            params.tag = props.activeTag.slug;
        }

        return params;
    }

    function getPostHref(post: PostItem): string {
        return route(postRouteName.value, getPostRouteParams(post));
    }

    function getBlogLandingHref(): string {
        const params = props.isGroup ? { blog: props.blogSlug } : { blog: props.blogSlug, mainDomain: props.mainDomain };

        if (!isMultiTagMode.value) {
            return `${route(ROUTE_NAMES.blogLanding, params)}${anchorId.value}`;
        }

        return getMultiTagFilterHref([], activeOperator.value, anchorId.value);
    }

    function getTagHref(tag: Tag): string {
        if (isMultiTagMode.value) {
            const selectedSlugs = new Set(selectedTags.value.map((activeTag) => activeTag.slug));
            if (selectedSlugs.has(tag.slug)) {
                selectedSlugs.delete(tag.slug);
            } else {
                selectedSlugs.add(tag.slug);
            }

            return getMultiTagFilterHref([...selectedSlugs], activeOperator.value, anchorId.value);
        }

        const params = props.isGroup
            ? { blog: props.blogSlug, tag: tag.slug }
            : { blog: props.blogSlug, mainDomain: props.mainDomain, tag: tag.slug };

        return `${route(ROUTE_NAMES.blogTag, params)}${anchorId.value}`;
    }

    function getMultiTagFilterHref(slugs: string[], operator: TagOperator, anchor: string): string {
        const params = { blog: props.blogSlug, mainDomain: props.mainDomain };
        const landingRoute = route(ROUTE_NAMES.blogLanding, params);
        const query = new URLSearchParams();

        if (typeof window !== 'undefined') {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.forEach((value, key) => {
                if (!key.startsWith('tags[') && !['tags', 'tag', 'operator', 'page'].includes(key)) {
                    query.append(key, value);
                }
            });
        }

        slugs.forEach((slug) => query.append('tags[]', slug));
        query.set('operator', operator);

        return `${landingRoute}${landingRoute.includes('?') ? '&' : '?'}${query.toString()}${anchor}`;
    }

    function getNewsletterHref(): string {
        return route(ROUTE_NAMES.newsletter, { blog_id: props.blogId });
    }

    return {
        anchorId,
        isMultiTagMode,
        selectedTags,
        activeOperator,
        getPostHref,
        getBlogLandingHref,
        getTagHref,
        getMultiTagFilterHref,
        getNewsletterHref,
    };
}
