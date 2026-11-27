import type { ManageableItem, AdminPostItem as PostItem } from '@/types/blog.types';
import type { MaybeRefOrGetter, Ref } from 'vue';
import { computed, toValue } from 'vue';
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
    item: MaybeRefOrGetter<TEntity>;
    entityState: BloggerEntityState<TEntity>;
    startCreatePost(postState: ReturnType<typeof usePostForm>): void;
}

export function useBloggerItemState<TEntity extends ManageableItem>({ item, entityState, startCreatePost }: UseBloggerItemStateOptions<TEntity>) {
    const postState = usePostForm();
    const uiState = useUIState();
    const getItem = () => toValue(item);

    const isEditing = computed(() => entityState.editingId.value === getItem().id);
    const isCreatingPost = computed(() => postState.creatingPostForId.value === getItem().id);
    const isPostsExpanded = computed(() => uiState.expandedPostsForId.value === getItem().id);

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

    function resetExtensionFormState() {
        postState.creatingExtensionForId.value = null;
        postState.editingExtensionId.value = null;
    }

    function resetPostInteraction() {
        resetPostCreation();
        resetPostEdit();
        resetExtensionFormState();
    }

    function edit() {
        resetPostInteraction();
        uiState.expandedPostsForId.value = null;
        entityState.startEdit(getItem());
    }

    function createPost() {
        resetEntityEdit();
        resetPostEdit();
        uiState.expandedPostsForId.value = null;
        resetExtensionFormState();
        startCreatePost(postState);
    }

    function togglePosts() {
        resetEntityEdit();
        resetPostCreation();
        resetPostEdit();
        resetExtensionFormState();
        uiState.togglePosts(getItem());
    }

    function editPost(post: PostItem) {
        resetEntityEdit();
        resetPostCreation();
        resetExtensionFormState();
        postState.startEditPost(post);
    }


    return {
        ...postState,
        ...uiState,
        isEditing,
        isCreatingPost,
        isPostsExpanded,
        editingPostId: postState.editingPostId,
        edit,
        createPost,
        togglePosts,
        editPost,
        submitEdit: () => entityState.submitEdit(getItem()),
    };
}
