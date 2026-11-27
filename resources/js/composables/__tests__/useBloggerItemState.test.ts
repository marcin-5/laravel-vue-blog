import type { AdminPostItem as PostItem } from '@/types/blog.types';
import { describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';
import { useBloggerItemState } from '../useBloggerItemState';

const mocks = vi.hoisted(() => ({
    postStates: [] as any[],
    uiStates: [] as any[],
}));

vi.mock('../usePostForm', () => ({
    usePostForm: () => mocks.postStates.shift(),
}));

vi.mock('../useUIState', () => ({
    useUIState: () => mocks.uiStates.shift(),
}));

function createPostState() {
    return {
        creatingPostForId: ref<number | null>(null),
        editingPostId: ref<number | null>(null),
        creatingExtensionForId: ref<number | null>(null),
        editingExtensionId: ref<number | null>(null),
        postForm: { reset: vi.fn() },
        postEditForm: { reset: vi.fn() },
        extensionForm: { reset: vi.fn() },
        extensionEditForm: { reset: vi.fn() },
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
        expandedPostsForId: ref<number | null>(null),
        togglePosts: vi.fn(),
    };
}

function createEntityState() {
    return {
        editingId: ref<number | null>(null),
        editForm: { reset: vi.fn() },
        startEdit: vi.fn(),
        cancelEdit: vi.fn(),
        submitEdit: vi.fn(),
    };
}

function createItem(id: number) {
    return { id, name: `Item ${id}`, slug: `item-${id}`, is_published: true };
}

describe('useBloggerItemState', () => {
    it('uses the latest item when editing or submitting after props change', () => {
        const postState = createPostState();
        const uiState = createUIState();
        const entityState = createEntityState();
        const item = ref(createItem(1));

        mocks.postStates.push(postState);
        mocks.uiStates.push(uiState);

        const state = useBloggerItemState({
            item: () => item.value,
            entityState,
            startCreatePost: vi.fn(),
        });
        const updatedItem = { ...createItem(1), name: 'Updated item' };

        item.value = updatedItem;
        state.edit();
        state.submitEdit();

        expect(entityState.startEdit).toHaveBeenCalledWith(updatedItem);
        expect(entityState.submitEdit).toHaveBeenCalledWith(updatedItem);
    });

    it('keeps state independent for two list items', () => {
        const firstPostState = createPostState();
        const secondPostState = createPostState();
        const firstUIState = createUIState();
        const secondUIState = createUIState();
        const firstEntityState = createEntityState();
        const secondEntityState = createEntityState();

        mocks.postStates.push(firstPostState, secondPostState);
        mocks.uiStates.push(firstUIState, secondUIState);

        const first = useBloggerItemState({
            item: createItem(1),
            entityState: firstEntityState,
            startCreatePost: (postState) => postState.startCreatePost(createItem(1) as any),
        });
        const second = useBloggerItemState({
            item: createItem(2),
            entityState: secondEntityState,
            startCreatePost: (postState) => postState.startCreatePost(createItem(2) as any),
        });

        first.createPost();
        first.togglePosts();

        expect(firstPostState.startCreatePost).toHaveBeenCalledOnce();
        expect(firstUIState.togglePosts).toHaveBeenCalledOnce();
        expect(secondPostState.startCreatePost).not.toHaveBeenCalled();
        expect(secondUIState.togglePosts).not.toHaveBeenCalled();
        expect(first.isCreatingPost.value).toBe(false);
        expect(second.isCreatingPost.value).toBe(false);
    });

    it('resets extension content forms before editing a post', () => {
        const postState = createPostState();
        const uiState = createUIState();
        const entityState = createEntityState();
        const post: PostItem = {
            id: 4,
            blog_id: 1,
            group_id: null,
            title: 'Post',
            slug: 'post',
            seo_title: null,
            excerpt: null,
            summary: null,
            content: null,
            is_published: true,
            visibility: 'public',
            related_posts: [],
            external_links: [],
        };

        mocks.postStates.push(postState);
        mocks.uiStates.push(uiState);

        postState.creatingExtensionForId.value = 6;
        postState.editingExtensionId.value = 8;

        const state = useBloggerItemState({
            item: createItem(1),
            entityState,
            startCreatePost: vi.fn(),
        });

        state.editPost(post);

        expect(postState.startEditPost).toHaveBeenCalledWith(post);
        expect(postState.creatingExtensionForId.value).toBeNull();
        expect(postState.editingExtensionId.value).toBeNull();
        expect(postState.postForm.reset).toHaveBeenCalled();
    });

    it('does not expose inline extension list state', () => {
        const postState = createPostState();
        const uiState = createUIState();
        const entityState = createEntityState();

        mocks.postStates.push(postState);
        mocks.uiStates.push(uiState);

        const state = useBloggerItemState({
            item: createItem(1),
            entityState,
            startCreatePost: vi.fn(),
        });

        expect(state).not.toHaveProperty('toggleExtensions');
        expect(uiState).not.toHaveProperty('expandedExtensionsForId');
    });
});
