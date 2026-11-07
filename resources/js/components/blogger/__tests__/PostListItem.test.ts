import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import PostListItem from '../PostListItem.vue';

vi.mock('@inertiajs/vue3', () => ({
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

vi.mock('@/components/blogger/PostExtensionManager.vue', () => ({
    default: { template: '<div />' },
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
        template: '<button @click="$emit(\'click\')"><slot /></button>',
    },
}));

describe('PostListItem.vue', () => {
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
                isExtensionsExpanded: false,
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