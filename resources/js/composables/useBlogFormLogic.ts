import type { Blog } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

interface BlogFormData {
    name: string;
    description: string | null;
    is_published: boolean;
    locale: string;
    sidebar: number;
    page_size: number;
    categories: number[];
}

interface UseBlogFormLogicOptions {
    blog?: Blog;
    isEdit?: boolean;
    externalForm?: any;
}

export function useBlogFormLogic(options: UseBlogFormLogicOptions = {}) {
    const { blog, isEdit = false, externalForm } = options;

    const form = externalForm || useForm<BlogFormData>({
        name: blog?.name || '',
        description: blog?.description || null,
        is_published: blog?.is_published || false,
        locale: (blog?.locale as string) || 'en',
        sidebar: (blog?.sidebar as number) ?? 0,
        page_size: (blog?.page_size as number) ?? 10,
        categories: (blog?.categories ?? []).map((c) => c.id) as number[],
    });

    const fieldIdPrefix = computed(() => {
        const base = isEdit ? 'edit-blog' : 'create-blog';
        const suffix = blog?.id || 'new';
        return `${base}-${suffix}`;
    });

    const updateFormFromBlog = (newBlog: Blog) => {
        form.name = newBlog.name;
        form.description = newBlog.description;
        form.is_published = newBlog.is_published;
        form.locale = (newBlog.locale as string) || 'en';
        form.sidebar = (newBlog.sidebar as number) ?? 0;
        form.page_size = (newBlog.page_size as number) ?? 10;
        form.categories = (newBlog.categories ?? []).map((c) => c.id);
    };

    if (!externalForm) {
        watch(
            () => blog,
            (newBlog) => {
                if (newBlog) {
                    updateFormFromBlog(newBlog);
                }
            },
            { immediate: true }
        );
    }

    const updateCategories = (categoryIds: number[]) => {
        form.categories = categoryIds;
    };

    return {
        form,
        fieldIdPrefix,
        updateFormFromBlog,
        updateCategories,
    };
}