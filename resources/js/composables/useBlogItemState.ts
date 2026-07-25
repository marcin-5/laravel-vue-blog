import type { AdminBlog as Blog } from '@/types/blog.types';
import { useBlogForm } from './useBlogForm';
import { useBloggerItemState } from './useBloggerItemState';

export function useBlogItemState(item: Blog) {
    const entityState = useBlogForm();
    const itemState = useBloggerItemState({
        item,
        entityState,
        startCreatePost: (postState) => postState.startCreatePost(item),
    });

    return {
        ...entityState,
        ...itemState,
    };
}
