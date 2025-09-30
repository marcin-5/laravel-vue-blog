import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import type { Blog, PostItem } from '@/types';

export function usePostForm() {
    const creatingPostForId = ref<number | null>(null);
    const editingPostId = ref<number | null>(null);

    const postForm = useForm({
        blog_id: 0 as number,
        title: '' as string,
        excerpt: '' as string,
        content: '' as string,
        is_published: false as boolean,
    });

    const postEditForm = useForm({
        title: '' as string,
        excerpt: '' as string | null,
        content: '' as string | null,
        is_published: false as boolean,
    });

    function startCreatePost(blog: Blog) {
        if (creatingPostForId.value === blog.id) {
            // Toggle off if this blog's Create Post form is already open
            cancelCreatePost();
            return;
        }

        creatingPostForId.value = blog.id;
        postForm.reset();
        postForm.blog_id = blog.id;
    }

    function cancelCreatePost() {
        creatingPostForId.value = null;
        postForm.reset();
    }

    function submitCreatePost() {
        postForm.post(route('posts.store'), {
            preserveScroll: true,
            onSuccess: () => {
                creatingPostForId.value = null;
                postForm.reset();
            },
        });
    }

    function startEditPost(post: PostItem) {
        if (editingPostId.value === post.id) {
            cancelEditPost();
            return;
        }

        editingPostId.value = post.id;
        postEditForm.reset();
        postEditForm.title = post.title;
        postEditForm.excerpt = post.excerpt ?? '';
        postEditForm.content = post.content ?? '';
        postEditForm.is_published = !!post.is_published;
    }

    function cancelEditPost() {
        editingPostId.value = null;
        postEditForm.reset();
    }

    function submitEditPost() {
        // Use editingPostId since we have the post ID stored in state
        const postId = editingPostId.value;

        postEditForm.patch(route('posts.update', [postId]), {
            preserveScroll: true,
            onSuccess: () => {
                editingPostId.value = null;
            },
        });
    }

    return {
        // State
        creatingPostForId,
        editingPostId,
        postForm,
        postEditForm,

        // Actions
        startCreatePost,
        cancelCreatePost,
        submitCreatePost,
        startEditPost,
        cancelEditPost,
        submitEditPost,
    };
}
