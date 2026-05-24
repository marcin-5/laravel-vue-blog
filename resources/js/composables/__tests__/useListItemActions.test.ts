import type { AdminPostItem, ListItemEmits, ManageableItem } from '@/types/blog.types';
import { describe, expect, it, vi } from 'vitest';
import { useListItemActions } from '../useListItemActions';

describe('useListItemActions', () => {
    it('handles edit and emits event', () => {
        const emit = vi.fn() as unknown as ListItemEmits<ManageableItem, AdminPostItem>;
        const { handleEdit, isEditing } = useListItemActions<ManageableItem, AdminPostItem>(emit);

        const item = { id: 1, name: 'Item', slug: 'item', is_published: true } as ManageableItem;
        handleEdit(item);

        expect(isEditing.value).toBe(true);
        expect(emit).toHaveBeenCalledWith('edit', item);
    });

    it('handles createPost and emits event', () => {
        const emit = vi.fn() as unknown as ListItemEmits<ManageableItem, AdminPostItem>;
        const { handleCreatePost, isCreatingPost } = useListItemActions<ManageableItem, AdminPostItem>(emit);

        const item = { id: 1, name: 'Item', slug: 'item', is_published: true } as ManageableItem;
        handleCreatePost(item);

        expect(isCreatingPost.value).toBe(true);
        expect(emit).toHaveBeenCalledWith('createPost', item);
    });

    it('handles togglePosts and emits event', () => {
        const emit = vi.fn() as unknown as ListItemEmits<ManageableItem, AdminPostItem>;
        const { handleTogglePosts, isPostsExpanded } = useListItemActions<ManageableItem, AdminPostItem>(emit);

        const item = { id: 1, name: 'Item', slug: 'item', is_published: true } as ManageableItem;
        handleTogglePosts(item);

        expect(isPostsExpanded.value).toBe(true);
        expect(emit).toHaveBeenCalledWith('togglePosts', item);

        handleTogglePosts(item);
        expect(isPostsExpanded.value).toBe(false);
    });

    it('handles editPost and emits event', () => {
        const emit = vi.fn() as unknown as ListItemEmits<ManageableItem, AdminPostItem>;
        const { handleEditPost, editingPostId } = useListItemActions<ManageableItem, AdminPostItem>(emit);

        const post = { id: 100, title: 'Post' } as AdminPostItem;
        handleEditPost(post);

        expect(editingPostId.value).toBe(100);
        expect(emit).toHaveBeenCalledWith('editPost', post);

        handleEditPost(post);
        expect(editingPostId.value).toBe(null);
    });

    it('handles cancelEditPost and emits event', () => {
        const emit = vi.fn() as unknown as ListItemEmits<ManageableItem, AdminPostItem>;
        const { handleCancelEditPost, editingPostId } = useListItemActions<ManageableItem, AdminPostItem>(emit);

        editingPostId.value = 100;
        handleCancelEditPost();

        expect(editingPostId.value).toBe(null);
        expect(emit).toHaveBeenCalledWith('cancelEditPost');
    });
});
