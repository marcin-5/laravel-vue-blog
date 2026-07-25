import type { AdminBlog } from '@/types/blog.types';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import BlogListItem from '../BlogListItem.vue';

const mocks = vi.hoisted(() => ({
    entityStates: [] as any[],
    postStates: [] as any[],
    uiStates: [] as any[],
}));

vi.mock('vue-i18n', () => ({
    createI18n: vi.fn(() => ({})),
    useI18n: () => ({ t: (key: string) => key }),
}));

vi.mock('@inertiajs/vue3', () => ({
    useForm: vi.fn(),
    router: { reload: vi.fn() },
}));

vi.mock('@/composables/useBlogForm', () => ({
    useBlogForm: () => mocks.entityStates.shift(),
}));

vi.mock('@/composables/usePostForm', () => ({
    usePostForm: () => mocks.postStates.shift(),
}));

vi.mock('@/composables/useUIState', () => ({
    useUIState: () => mocks.uiStates.shift(),
}));

vi.mock('@/utils/localization', () => ({
    localizedName: (name: string) => name,
}));

vi.mock('@/components/blogger/BloggerListItem.vue', () => ({
    default: {
        name: 'BloggerListItem',
        props: ['context'],
        emits: ['create-post'],
        template: '<button type="button" @click="$emit(\'create-post\', context.item)">{{ context.item.name }}</button>',
    },
}));
vi.mock('@/components/blogger/BlogForm.vue', () => ({ default: { template: '<div />' } }));
vi.mock('@/components/blogger/ItemActionGroup.vue', () => ({ default: { template: '<div />' } }));
vi.mock('@/components/blogger/PostForm.vue', () => ({ default: { template: '<div />' } }));

function createEntityState() {
    return {
        showCreate: { value: false },
        editingId: { value: null },
        createForm: {},
        editForm: { reset: vi.fn() },
        openCreateForm: vi.fn(),
        closeCreateForm: vi.fn(),
        submitCreate: vi.fn(),
        startEdit: vi.fn(),
        cancelEdit: vi.fn(),
        submitEdit: vi.fn(),
    };
}

function createPostState() {
    return {
        creatingPostForId: { value: null },
        editingPostId: { value: null },
        creatingExtensionForId: { value: null },
        editingExtensionId: { value: null },
        postForm: { reset: vi.fn() },
        postEditForm: { reset: vi.fn() },
        extensionForm: {},
        extensionEditForm: {},
        startCreatePost: vi.fn(),
        startCreatePostInGroup: vi.fn(),
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
    };
}

function createUIState() {
    return {
        expandedPostsForId: { value: null },
        expandedExtensionsForId: { value: null },
        togglePosts: vi.fn(),
        toggleExtensions: vi.fn(),
    };
}

function createBlog(id: number): AdminBlog {
    return {
        id,
        user_id: 1,
        name: `Blog ${id}`,
        slug: `blog-${id}`,
        seo_title: null,
        seo_description: null,
        description: null,
        is_published: true,
        locale: 'en',
    };
}

describe('BlogListItem.vue', () => {
    it('keeps create-post actions isolated between mounted items', async () => {
        const firstPostState = createPostState();
        const secondPostState = createPostState();

        mocks.entityStates.push(createEntityState(), createEntityState());
        mocks.postStates.push(firstPostState, secondPostState);
        mocks.uiStates.push(createUIState(), createUIState());

        const wrapper = mount({
            components: { BlogListItem },
            template: '<div><BlogListItem v-for="blog in blogs" :key="blog.id" :categories="[]" :item="blog" /></div>',
            data: () => ({ blogs: [createBlog(1), createBlog(2)] }),
        });

        wrapper.findAllComponents({ name: 'BloggerListItem' })[0].props('context').actions.createPost();

        expect(firstPostState.startCreatePost).toHaveBeenCalledWith(createBlog(1));
        expect(secondPostState.startCreatePost).not.toHaveBeenCalled();
    });
});
