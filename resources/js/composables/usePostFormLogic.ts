import type { PostItem } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

interface PostFormData {
    blog_id: number;
    title: string;
    excerpt: string;
    content: string;
    is_published: boolean;
    [key: string]: any; // Index signature for Inertia form compatibility
}

interface UsePostFormLogicOptions {
    post?: PostItem;
    blogId?: number;
    isEdit?: boolean;
    externalForm?: any;
}

export function usePostFormLogic(options: UsePostFormLogicOptions = {}) {
    const { post, blogId, isEdit = false, externalForm } = options;

    const form =
        externalForm ||
        useForm<PostFormData>({
            blog_id: blogId ?? post?.blog_id ?? 0,
            title: post?.title ?? '',
            excerpt: post?.excerpt ?? '',
            content: post?.content ?? '',
            is_published: post?.is_published ?? false,
        });

    const fieldIdPrefix = computed(() => {
        const base = isEdit ? 'edit-post' : 'create-post';
        const suffix = post?.id || blogId || 'new';
        return `${base}-${suffix}`;
    });

    const updateFormFromPost = (newPost: PostItem) => {
        form.blog_id = newPost.blog_id;
        form.title = newPost.title;
        form.excerpt = newPost.excerpt ?? '';
        form.content = newPost.content ?? '';
        form.is_published = newPost.is_published;
    };

    if (!externalForm) {
        watch(
            () => post,
            (newPost) => {
                if (newPost) {
                    updateFormFromPost(newPost);
                }
            },
            { immediate: true },
        );

        watch(
            () => blogId,
            (newBlogId) => {
                if (newBlogId && !isEdit) {
                    form.blog_id = newBlogId;
                }
            },
            { immediate: true },
        );
    }

    return {
        form,
        fieldIdPrefix,
        updateFormFromPost,
    };
}
