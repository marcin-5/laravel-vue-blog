import { router } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import PostListItem from '../PostListItem.vue';

const routeMock = vi.fn(() => 'extensions-url');

vi.mock('@inertiajs/vue3', () => ({
    router: { visit: vi.fn() },
    usePage: () => ({
        props: {
            blogs: [{ id: 4, slug: 'my-blog', main_domain: 'example.com' }],
        },
    }),
}));

vi.mock('vue-i18n', () => ({
    useI18n: () => ({ t: (key: string) => key }),
}));

vi.mock('@/composables/useToast', () => ({
    useToast: () => ({ toast: vi.fn() }),
}));

vi.mock('@/components/blogger/PostForm.vue', () => ({
    default: { template: '<div />' },
}));

vi.mock('@/components/ui/badge', () => ({
    Badge: { template: '<div><slot /></div>' },
}));

vi.mock('@/components/ui/tooltip', () => ({
    TooltipButton: {
        name: 'TooltipButton',
        props: ['tooltipContent'],
        inheritAttrs: false,
        template: '<button v-bind="$attrs" @click="$emit(\'click\')"><slot /></button>',
    },
}));

describe('PostListItem.vue', () => {
    it('navigates to extension management with its source context', async () => {
        (global as any).route = routeMock;

        const wrapper = mount(PostListItem, {
            props: {
                post: {
                    id: 9,
                    blog_id: 4,
                    group_id: 7,
                    title: 'Post',
                    slug: 'post',
                    visibility: 'public',
                } as any,
                isEditing: false,
                sourceContext: 'group',
            },
        });

        await wrapper.get('[data-testid="manage-extensions"]').trigger('click');

        expect(routeMock).toHaveBeenCalledWith('blogger.posts.extensions.index', {
            post: 9,
            context: 'group',
        });
        expect(router.visit).toHaveBeenCalledWith('extensions-url');
    });
    it('copies the unlisted post URL using its blog subdomain', async () => {
        const writeText = vi.fn().mockResolvedValue(undefined);
        Object.defineProperty(navigator, 'clipboard', {
            configurable: true,
            value: { writeText },
        });
        (global as any).route = vi.fn(() => 'https://my-blog.example.com/my-post');

        const wrapper = mount(PostListItem, {
            props: {
                post: {
                    id: 9,
                    blog_id: 4,
                    title: 'Post',
                    slug: 'my-post',
                    visibility: 'unlisted',
                } as any,
                isEditing: false,
                sourceContext: 'blog',
            },
        });

        await wrapper.find('button').trigger('click');

        expect((global as any).route).toHaveBeenCalledWith('blog.public.post', {
            blog: 'my-blog',
            mainDomain: 'example.com',
            postSlug: 'my-post',
        });
        expect(writeText).toHaveBeenCalledWith('https://my-blog.example.com/my-post');
    });
});
