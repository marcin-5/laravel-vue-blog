import BlogListItem from '@/components/blogger/BlogListItem.vue';
import BlogCreateSection from '@/pages/app/blogger/components/BlogCreateSection.vue';
import BlogList from '@/pages/app/blogger/components/BlogList.vue';
import type { AdminBlog, Category } from '@/types/blog.types';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import Blogs from '../Blogs.vue';

const openCreateForm = vi.fn();

vi.mock('vue-i18n', () => ({
    useI18n: () => ({
        t: (key: string) => key,
    }),
}));

vi.mock('@inertiajs/vue3', () => ({
    useForm: vi.fn(() => ({
        processing: false,
        errors: {},
        reset: vi.fn(),
    })),
    router: {
        reload: vi.fn(),
    },
}));

vi.mock('@/layouts/AppLayout.vue', () => ({
    default: { name: 'AppLayout', template: '<div><slot /></div>' },
}));

vi.mock('@/components/blogger/BlogForm.vue', () => ({
    default: { name: 'BlogForm', template: '<div>BlogForm</div>' },
}));
vi.mock('@/components/blogger/BlogListItem.vue', () => ({
    default: { name: 'BlogListItem', template: '<div>BlogListItem</div>' },
}));
vi.mock('@/components/blogger/CreateEntitySection.vue', () => ({
    default: {
        name: 'CreateEntitySection',
        template: '<div><slot name="form" :form="{}" :onCancel="() => {}" :onSubmit="() => {}" /></div>',
    },
}));
vi.mock('@/components/ui/button', () => ({
    Button: { name: 'Button', inheritAttrs: false, template: '<button v-bind="$attrs"><slot /></button>' },
}));

vi.mock('@/composables/useBlogForm', () => ({
    useBlogForm: () => ({
        showCreate: { value: false },
        editingId: { value: null },
        createForm: {},
        editForm: {},
        openCreateForm,
        closeCreateForm: vi.fn(),
        submitCreate: vi.fn(),
        startEdit: vi.fn(),
        cancelEdit: vi.fn(),
        submitEdit: vi.fn(),
    }),
}));

vi.mock('@/composables/usePostForm', () => ({
    usePostForm: () => ({
        creatingPostForId: { value: null },
        editingPostId: { value: null },
        creatingExtensionForId: { value: null },
        editingExtensionId: { value: null },
        postForm: { reset: vi.fn() },
        postEditForm: { reset: vi.fn() },
        extensionForm: {},
        extensionEditForm: {},
        startCreatePost: vi.fn(),
        cancelCreatePost: vi.fn(),
        submitCreatePost: vi.fn(),
        startEditPost: vi.fn(),
        cancelEditPost: vi.fn(),
        submitEditPost: vi.fn(),
        startCreateExtension: vi.fn(),
        cancelCreateExtension: vi.fn(),
        submitCreateExtension: vi.fn(),
        startEditExtension: vi.fn(),
        cancelEditExtension: vi.fn(),
        submitEditExtension: vi.fn(),
    }),
}));

vi.mock('@/composables/useUIState', () => ({
    useUIState: () => ({
        expandedPostsForId: { value: null },
        expandedExtensionsForId: { value: null },
        togglePosts: vi.fn(),
        toggleExtensions: vi.fn(),
    }),
}));

describe('Blogs.vue', () => {
    const mockBlogs: AdminBlog[] = [
        {
            id: 1,
            user_id: 1,
            name: 'Blog 1',
            slug: 'blog-1',
            seo_title: null,
            seo_description: null,
            description: null,
            is_published: true,
            locale: 'en',
        },
        {
            id: 2,
            user_id: 1,
            name: 'Blog 2',
            slug: 'blog-2',
            seo_title: null,
            seo_description: null,
            description: null,
            is_published: true,
            locale: 'en',
        },
    ];
    const categories: Category[] = [];

    it('composes the create section and blog list with page props', () => {
        const wrapper = mount(Blogs, {
            props: { blogs: mockBlogs, canCreate: true, categories },
            global: { stubs: { Head: true } },
        });

        expect(wrapper.findComponent(BlogCreateSection).exists()).toBe(true);
        expect(wrapper.findComponent(BlogList).props()).toMatchObject({ blogs: mockBlogs, canCreate: true, categories });
        expect(wrapper.findAllComponents(BlogListItem)).toHaveLength(mockBlogs.length);
    });

    it('renders the empty state and opens the create form from its CTA', async () => {
        const wrapper = mount(Blogs, {
            props: { blogs: [], canCreate: true, categories },
            global: { stubs: { Head: true } },
        });

        expect(wrapper.text()).toContain('blogger.empty');

        await wrapper.find('button').trigger('click');

        expect(openCreateForm).toHaveBeenCalledOnce();
    });

    it('keeps the empty-state CTA disabled when creation is unavailable', () => {
        const wrapper = mount(Blogs, {
            props: { blogs: [], canCreate: false, categories },
            global: { stubs: { Head: true } },
        });

        expect(wrapper.find('button').attributes('disabled')).toBeDefined();
        expect(wrapper.findComponent(BlogList).props('canCreate')).toBe(false);
    });
});
