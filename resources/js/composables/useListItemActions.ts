import { ref } from 'vue';

export function useListItemActions<T, P>(emit: any) {
    const isEditing = ref(false);
    const isCreatingPost = ref(false);
    const isPostsExpanded = ref(false);
    const editingPostId = ref<number | null>(null);

    function toggleEdit() {
        isEditing.value = !isEditing.value;
        if (isEditing.value) {
            isCreatingPost.value = false;
        }
    }

    function toggleCreatePost() {
        isCreatingPost.value = !isCreatingPost.value;
        if (isCreatingPost.value) {
            isEditing.value = false;
        }
    }

    function togglePosts() {
        isPostsExpanded.value = !isPostsExpanded.value;
    }

    function handleEdit(item: T) {
        toggleEdit();
        emit('edit', item);
    }

    function handleCreatePost(item: T) {
        toggleCreatePost();
        emit('createPost', item);
    }

    function handleTogglePosts(item: T) {
        togglePosts();
        emit('togglePosts', item);
    }

    function handleEditPost(post: P) {
        editingPostId.value = editingPostId.value === (post as any).id ? null : (post as any).id;
        emit('editPost', post);
    }

    function handleCancelEditPost() {
        editingPostId.value = null;
        emit('cancelEditPost');
    }

    return {
        isEditing,
        isCreatingPost,
        isPostsExpanded,
        editingPostId,
        toggleEdit,
        toggleCreatePost,
        togglePosts,
        handleEdit,
        handleCreatePost,
        handleTogglePosts,
        handleEditPost,
        handleCancelEditPost,
    };
}
