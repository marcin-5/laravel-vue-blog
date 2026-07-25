import type { ManageableItem, AdminPostItem as PostItem } from '@/types/blog.types';
import type { Ref } from 'vue';
import { computed } from 'vue';
import { usePostForm } from './usePostForm';
import { useUIState } from './useUIState';

export interface BloggerEntityState<TEntity extends ManageableItem> {
    editingId: Ref<number | null>;
    editForm: any;
    startEdit(entity: TEntity): void;
    cancelEdit(): void;
    submitEdit(entity: TEntity): void;
}

export interface UseBloggerItemStateOptions<TEntity extends ManageableItem> {
    item: TEntity;
    entityState: BloggerEntityState<TEntity>;
    startCreatePost(postState: ReturnType<typeof usePostForm>): void;
}

export function useBloggerItemState<TEntity extends ManageableItem>({ item, entityState, startCreatePost }: UseBloggerItemStateOptions<TEntity>) {
    const postState = usePostForm();
    const uiState = useUIState();

    const isEditing = computed(() => entityState.editingId.value === item.id);
    const isCreatingPost = computed(() => postState.creatingPostForId.value === item.id);
    const isPostsExpanded = computed(() => uiState.expandedPostsForId.value === item.id);

    function resetPostInteraction() {
        postState.creatingPostForId.value = null;
        postState.editingPostId.value = null;
        postState.creatingExtensionForId.value = null;
        postState.editingExtensionId.value = null;
        uiState.expandedExtensionsForId.value = null;
        postState.postForm.reset();
        postState.postEditForm.reset();
    }

    function edit() {
        resetPostInteraction();
        uiState.expandedPostsForId.value = null;
        entityState.startEdit(item);
    }

    function createPost() {
        entityState.editingId.value = null;
        entityState.editForm.reset();
        postState.editingPostId.value = null;
        postState.postEditForm.reset();
        uiState.expandedPostsForId.value = null;
        uiState.expandedExtensionsForId.value = null;
        postState.creatingExtensionForId.value = null;
        postState.editingExtensionId.value = null;
        startCreatePost(postState);
    }

    function togglePosts() {
        entityState.editingId.value = null;
        entityState.editForm.reset();
        postState.creatingPostForId.value = null;
        postState.editingPostId.value = null;
        postState.creatingExtensionForId.value = null;
        postState.editingExtensionId.value = null;
        uiState.expandedExtensionsForId.value = null;
        postState.postForm.reset();
        postState.postEditForm.reset();
        uiState.togglePosts(item);
    }

    function editPost(post: PostItem) {
        entityState.editingId.value = null;
        entityState.editForm.reset();
        postState.creatingPostForId.value = null;
        postState.postForm.reset();
        uiState.expandedExtensionsForId.value = null;
        postState.creatingExtensionForId.value = null;
        postState.editingExtensionId.value = null;
        postState.startEditPost(post);
    }

    function toggleExtensions(post: PostItem) {
        entityState.editingId.value = null;
        entityState.editForm.reset();
        postState.creatingPostForId.value = null;
        postState.editingPostId.value = null;
        postState.creatingExtensionForId.value = null;
        postState.editingExtensionId.value = null;
        postState.postForm.reset();
        postState.postEditForm.reset();
        uiState.toggleExtensions(post);
    }

    return {
        ...postState,
        ...uiState,
        isEditing,
        isCreatingPost,
        isPostsExpanded,
        editingPostId: postState.editingPostId,
        expandedExtensionsForId: uiState.expandedExtensionsForId,
        edit,
        createPost,
        togglePosts,
        editPost,
        toggleExtensions,
        submitEdit: () => entityState.submitEdit(item),
    };
}
