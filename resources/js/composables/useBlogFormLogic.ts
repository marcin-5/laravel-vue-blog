import type { AdminBlog as Blog, BlogFormData, UseBlogFormLogicOptions } from '@/types/blog.types';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

export function useBlogFormLogic(options: UseBlogFormLogicOptions = {}) {
    const { isEdit = false, externalForm } = options;

    const form =
        externalForm ||
        useForm<BlogFormData>({
            name: options.blog?.name || '',
            description: options.blog?.description || null,
            footer: options.blog?.footer || null,
            motto: options.blog?.motto || null,
            is_published: options.blog?.is_published || false,
            locale: (options.blog?.locale as string) || 'en',
            sidebar: (options.blog?.sidebar as number) ?? 0,
            page_size: (options.blog?.page_size as number) ?? 10,
            categories: (options.blog?.categories ?? []).map((c) => c.id) as number[],
        });

    const fieldIdPrefix = computed(() => {
        const base = isEdit ? 'edit-blog' : 'create-blog';
        const suffix = options.blog?.id || 'new';
        return `${base}-${suffix}`;
    });

    const updateFormFromBlog = (newBlog: Blog) => {
        form.name = newBlog.name;
        form.description = newBlog.description;
        form.footer = newBlog.footer ?? null;
        form.motto = newBlog.motto ?? null;
        form.is_published = newBlog.is_published;
        form.locale = (newBlog.locale as string) || 'en';
        form.sidebar = (newBlog.sidebar as number) ?? 0;
        form.page_size = (newBlog.page_size as number) ?? 10;
        form.categories = (newBlog.categories ?? []).map((c) => c.id);
    };

    if (!externalForm) {
        watch(
            () => options.blog,
            (newBlog) => {
                if (newBlog) {
                    updateFormFromBlog(newBlog);
                }
            },
            { immediate: true },
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
