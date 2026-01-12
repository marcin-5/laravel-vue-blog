import type { AdminBlog as Blog } from '@/types/blog.types';
import { ref } from 'vue';

export function useUIState() {
    const expandedPostsForId = ref<number | null>(null);
    const expandedExtensionsForId = ref<number | null>(null);

    function togglePosts(blog: Blog) {
        if (expandedPostsForId.value === blog.id) {
            expandedPostsForId.value = null;
            return;
        }

        expandedPostsForId.value = blog.id;
    }

    function toggleExtensions(post: { id: number }) {
        if (expandedExtensionsForId.value === post.id) {
            expandedExtensionsForId.value = null;
            return;
        }

        expandedExtensionsForId.value = post.id;
    }

    function hidePosts() {
        expandedPostsForId.value = null;
    }

    function hideExtensions() {
        expandedExtensionsForId.value = null;
    }

    function hideAllForms() {
        expandedPostsForId.value = null;
        expandedExtensionsForId.value = null;
    }

    return {
        // State
        expandedPostsForId,
        expandedExtensionsForId,

        // Actions
        togglePosts,
        toggleExtensions,
        hidePosts,
        hideExtensions,
        hideAllForms,
    };
}
