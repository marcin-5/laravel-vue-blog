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

    function resetEntityEdit() {
        entityState.editingId.value = null;
        entityState.editForm.reset();
    }

    function resetPostCreation() {
        postState.creatingPostForId.value = null;
        postState.postForm.reset();
    }

    function resetPostEdit() {
        postState.editingPostId.value = null;
        postState.postEditForm.reset();
    }

    function resetExtensionInteraction() {
        postState.creatingExtensionForId.value = null;
        postState.editingExtensionId.value = null;
        uiState.expandedExtensionsForId.value = null;
    }

    function resetPostInteraction() {
        resetPostCreation();
        resetPostEdit();
        resetExtensionInteraction();
    }

    function edit() {
        resetPostInteraction();
        uiState.expandedPostsForId.value = null;
        entityState.startEdit(item);
    }

    function createPost() {
        resetEntityEdit();
        resetPostEdit();
        uiState.expandedPostsForId.value = null;
        resetExtensionInteraction();
        startCreatePost(postState);
    }

    function togglePosts() {
        resetEntityEdit();
        resetPostCreation();
        resetPostEdit();
        resetExtensionInteraction();
        uiState.togglePosts(item);
    }

    function editPost(post: PostItem) {
        resetEntityEdit();
        resetPostCreation();
        resetExtensionInteraction();
        postState.startEditPost(post);
    }

    function toggleExtensions(post: PostItem) {
        resetEntityEdit();
        resetPostCreation();
        resetPostEdit();
        resetExtensionInteraction();
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
