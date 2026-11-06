import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import BlogPostsList from '../BlogPostsList.vue';

const { visit } = vi.hoisted(() => ({ visit: vi.fn() }));

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
        router: { visit },
    };
});

vi.mock('vue-i18n', () => ({
    useI18n: () => ({
        t: (key: string) =>
            ({
                'blog.posts_list.tag_conjunction_and': ' i ',
                'blog.posts_list.tag_conjunction_or': ' lub ',
            })[key] ?? key,
    }),
}));
vi.mock('@/composables/useBlogExcerpts', () => ({ useBlogExcerpts: () => ({ showExcerpts: { value: false } }) }));
vi.mock('@/components/ui/switch', () => ({ Switch: { template: '<input type="checkbox" />' } }));
vi.mock('@/components/ui/tooltip', () => ({
    Tooltip: { template: '<div><slot /></div>' },
    TooltipContent: { template: '<div><slot /></div>' },
    TooltipProvider: { template: '<div><slot /></div>' },
    TooltipTrigger: { template: '<div><slot /></div>' },
}));
vi.mock('lucide-vue-next', () => ({ Info: { template: '<span />' }, Mail: { template: '<span />' } }));

const tagA = { id: 1, blog_id: 7, name: 'Alpha', slug: 'alpha' };
const tagB = { id: 2, blog_id: 7, name: 'Beta', slug: 'beta' };

function mountList(props: Record<string, unknown> = {}) {
    return mount(BlogPostsList, {
        props: {
            posts: [],
            blogSlug: 'sample-blog',
            mainDomain: 'example.test',
            blogId: 7,
            allTags: [tagA, tagB],
            ...props,
        },
    });
}

describe('BlogPostsList multi-tag filters', () => {
    beforeEach(() => {
        visit.mockReset();
        vi.stubGlobal('route', (name: string, params: Record<string, string>) => {
            if (name === 'blog.public.tag') {
                return `/blogs/${params.blog}/tags/${params.tag}`;
            }

            return `/blogs/${params.blog}`;
        });
        window.history.replaceState({}, '', `${window.location.origin}/blogs/sample-blog?filter=recent&page=3`);
    });

    it('defaults the operator to AND and restores selections from the payload', () => {
        const wrapper = mountList({
            allowMultipleTagFiltering: true,
            activeTags: [tagA, tagB],
            operator: undefined,
        });
        const alphaLink = wrapper.findAll('a').find((link) => link.text() === '#Alpha');

        expect(wrapper.find('button[aria-pressed="true"]').text()).toBe('blog.posts_list.operator_and');
        expect(wrapper.find('a[href*="tags%5B%5D=alpha"]').exists()).toBe(true);
        expect(alphaLink?.classes()).toContain('bg-link/10');
        expect(new URL(alphaLink!.attributes('href')!, window.location.origin).searchParams.getAll('tags[]')).toEqual(['beta']);
        expect(wrapper.find('[role="group"]').exists()).toBe(true);
    });

    it('shows the operator and conjunction only for multiple selected tags', () => {
        const wrapper = mountList({
            allowMultipleTagFiltering: true,
            activeTags: [tagA, tagB],
            operator: 'OR',
        });

        expect(wrapper.find('[role="group"]').exists()).toBe(true);
        expect(wrapper.find('h2').text()).toContain('blog.posts_list.active_tags: Alpha lub Beta');

        const andWrapper = mountList({ allowMultipleTagFiltering: true, activeTags: [tagA, tagB], operator: 'AND' });
        expect(andWrapper.find('h2').text()).toContain('blog.posts_list.active_tags: Alpha i Beta');
    });

    it('uses the singular title and hides the operator for one selected tag', () => {
        const wrapper = mountList({ allowMultipleTagFiltering: true, activeTags: [tagA] });

        expect(wrapper.find('[role="group"]').exists()).toBe(false);
        expect(wrapper.find('h2').text()).toContain('blog.posts_list.active_tag: Alpha');
    });

    it('toggles tags while preserving unrelated query parameters and the selected operator', () => {
        const wrapper = mountList({
            allowMultipleTagFiltering: true,
            activeTags: [tagA],
            operator: 'OR',
        });
        const betaLink = wrapper.findAll('a').find((link) => link.text() === '#Beta');
        const url = new URL(betaLink!.attributes('href')!, window.location.origin);

        expect(url.searchParams.getAll('tags[]')).toEqual(['alpha', 'beta']);
        expect(url.searchParams.get('operator')).toBe('OR');
        expect(url.searchParams.get('filter')).toBe('recent');
        expect(url.searchParams.has('page')).toBe(false);
    });

    it('keeps the operator when clearing the tag filter', () => {
        const wrapper = mountList({
            allowMultipleTagFiltering: true,
            activeTags: [tagA, tagB],
            operator: 'OR',
        });
        const clearLink = wrapper.findAll('a').find((link) => link.text() === 'blog.posts_list.clear_filter');
        const url = new URL(clearLink!.attributes('href')!, window.location.origin);

        expect(url.searchParams.has('tags[]')).toBe(false);
        expect(url.searchParams.get('operator')).toBe('OR');
    });

    it('updates the operator in the URL while retaining tag selections', async () => {
        const wrapper = mountList({
            allowMultipleTagFiltering: true,
            activeTags: [tagA, tagB],
            operator: 'AND',
        });

        await wrapper.find('button[aria-pressed="false"]').trigger('click');

        const [href] = visit.mock.calls[0];
        const url = new URL(href, window.location.origin);
        expect(url.searchParams.getAll('tags[]')).toEqual(['alpha', 'beta']);
        expect(url.searchParams.get('operator')).toBe('OR');
    });

    it('keeps the existing single-tag URL when multiple tag filtering is disabled', () => {
        const wrapper = mountList({ activeTag: tagA });
        const betaLink = wrapper.findAll('a').find((link) => link.text() === '#Beta');

        expect(betaLink?.attributes('href')).toBe('/blogs/sample-blog/tags/beta#posts-list');
        expect(wrapper.find('[role="group"]').exists()).toBe(false);
    });
});
