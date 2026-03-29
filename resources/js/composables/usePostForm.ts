import type { AdminBlog as Blog, AdminGroup as Group, AdminPostItem as PostItem, PostExtension } from '@/types/blog.types';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

export function usePostForm() {
    const creatingPostForId = ref<number | null>(null);
    const editingPostId = ref<number | null>(null);

    const creatingExtensionForId = ref<number | null>(null);
    const editingExtensionId = ref<number | null>(null);

    const postForm = useForm({
        blog_id: null as number | null,
        group_id: null as number | null,
        title: '' as string,
        seo_title: '' as string,
        excerpt: '' as string,
        summary: '' as string,
        content: '' as string,
        is_published: false as boolean,
        visibility: 'public' as string,
        related_posts: [] as any[],
        external_links: [] as any[],
    });

    const postEditForm = useForm({
        title: '' as string,
        seo_title: '' as string,
        excerpt: '' as string | null,
        summary: '' as string | null,
        content: '' as string | null,
        is_published: false as boolean,
        visibility: 'public' as string,
        related_posts: [] as any[],
        external_links: [] as any[],
    });

    const extensionForm = useForm({
        title: '' as string,
        content: '' as string,
        is_published: false as boolean,
    });

    const extensionEditForm = useForm({
        title: '' as string,
        content: '' as string,
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
        postForm.group_id = null;
    }

    function startCreatePostInGroup(group: Group) {
        if (creatingPostForId.value === group.id) {
            cancelCreatePost();
            return;
        }

        creatingPostForId.value = group.id;
        postForm.reset();
        postForm.group_id = group.id;
        postForm.blog_id = null;
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
        postEditForm.seo_title = post.seo_title ?? '';
        postEditForm.excerpt = post.excerpt ?? '';
        postEditForm.summary = post.summary ?? '';
        postEditForm.content = post.content ?? '';
        postEditForm.is_published = post.is_published;
        postEditForm.visibility = post.visibility ?? 'public';
        postEditForm.related_posts = [...(post.related_posts || [])];
        postEditForm.external_links = [...(post.external_links || [])];
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

    function startCreateExtension(post: PostItem) {
        if (creatingExtensionForId.value === post.id) {
            cancelCreateExtension();
            return;
        }

        creatingExtensionForId.value = post.id;
        extensionForm.reset();
    }

    function cancelCreateExtension() {
        creatingExtensionForId.value = null;
        extensionForm.reset();
    }

    function submitCreateExtension(post: PostItem) {
        extensionForm.post(route('post-extensions.store', [post.id]), {
            preserveScroll: true,
            onSuccess: () => {
                creatingExtensionForId.value = null;
                extensionForm.reset();
            },
        });
    }

    function startEditExtension(extension: PostExtension) {
        if (editingExtensionId.value === extension.id) {
            cancelEditExtension();
            return;
        }

        editingExtensionId.value = extension.id;
        extensionEditForm.reset();
        extensionEditForm.title = extension.title;
        extensionEditForm.content = extension.content;
        extensionEditForm.is_published = extension.is_published ?? false;
    }

    function cancelEditExtension() {
        editingExtensionId.value = null;
        extensionEditForm.reset();
    }

    function submitEditExtension(extension: PostExtension, closeOnSuccess: boolean = true) {
        extensionEditForm.patch(route('post-extensions.update', [extension.id]), {
            preserveScroll: true,
            onSuccess: () => {
                if (closeOnSuccess) {
                    editingExtensionId.value = null;
                }
            },
        });
    }

    return {
        // State
        creatingPostForId,
        editingPostId,
        creatingExtensionForId,
        editingExtensionId,
        postForm,
        postEditForm,
        extensionForm,
        extensionEditForm,

        // Actions
        startCreatePost,
        startCreatePostInGroup,
        cancelCreatePost,
        submitCreatePost,
        startEditPost,
        cancelEditPost,
        submitEditPost,
        startCreateExtension,
        cancelCreateExtension,
        submitCreateExtension,
        startEditExtension,
        cancelEditExtension,
        submitEditExtension,
    };
}
