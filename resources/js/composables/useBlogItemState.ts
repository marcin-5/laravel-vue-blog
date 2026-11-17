import type { AdminBlog as Blog } from '@/types/blog.types';
import { useBlogForm } from './useBlogForm';
import { useBloggerItemState } from './useBloggerItemState';
import type { MaybeRefOrGetter } from 'vue';
import { toValue } from 'vue';

export function useBlogItemState(item: MaybeRefOrGetter<Blog>) {
    const entityState = useBlogForm();
    const itemState = useBloggerItemState({
        item,
        entityState,
        startCreatePost: (postState) => postState.startCreatePost(toValue(item)),
    });

    return {
        ...entityState,
        ...itemState,
    };
}
