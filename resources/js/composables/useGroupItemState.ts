import type { AdminGroup as Group } from '@/types/blog.types';
import { useBloggerItemState } from './useBloggerItemState';
import { useGroupForm } from './useGroupForm';

export function useGroupItemState(item: Group) {
    const entityState = useGroupForm();
    const itemState = useBloggerItemState({
        item,
        entityState,
        startCreatePost: (postState) => postState.startCreatePostInGroup(item),
    });

    return {
        ...entityState,
        ...itemState,
    };
}
