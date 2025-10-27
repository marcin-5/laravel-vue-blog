import type { AdminBlog as Blog } from '@/types/blog.types';
import { ref } from 'vue';

export function useUIState() {
    const expandedPostsForId = ref<number | null>(null);

    function togglePosts(blog: Blog) {
        if (expandedPostsForId.value === blog.id) {
            expandedPostsForId.value = null;
            return;
        }

        expandedPostsForId.value = blog.id;
    }

    function hidePosts() {
        expandedPostsForId.value = null;
    }

    function hideAllForms() {
        expandedPostsForId.value = null;
    }

    return {
        // State
        expandedPostsForId,

        // Actions
        togglePosts,
        hidePosts,
        hideAllForms,
    };
}
