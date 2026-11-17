import type { AdminGroup as Group } from '@/types/blog.types';
import type { MaybeRefOrGetter } from 'vue';
import { toValue } from 'vue';
import { useBloggerItemState } from './useBloggerItemState';
import { useGroupForm } from './useGroupForm';

export function useGroupItemState(item: MaybeRefOrGetter<Group>) {
    const entityState = useGroupForm();
    const itemState = useBloggerItemState({
        item,
        entityState,
        startCreatePost: (postState) => postState.startCreatePostInGroup(toValue(item)),
    });

    return {
        ...entityState,
        ...itemState,
    };
}
