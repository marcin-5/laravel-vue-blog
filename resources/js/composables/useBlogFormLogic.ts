import type { BlogFormData, UseBlogFormLogicOptions } from '@/types/blog.types';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { createFormDataFromBlog, ensureThemeStructure, populateFormFromBlog } from './blogFormUtils';

export function useBlogFormLogic(options: UseBlogFormLogicOptions = {}) {
    const { isEdit = false, externalForm } = options;

    const form = externalForm || useForm<BlogFormData>(createFormDataFromBlog(options.blog));

    const fieldIdPrefix = computed(() => {
        const base = isEdit ? 'edit-blog' : 'create-blog';
        const suffix = options.blog?.id ?? 'new';
        return `${base}-${suffix}`;
    });

    // Ensure theme structure exists for new forms
    form.theme = ensureThemeStructure(form.theme);

    if (!externalForm) {
        watch(
            () => options.blog,
            (newBlog) => {
                if (newBlog) {
                    populateFormFromBlog(form, newBlog);
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
        updateCategories,
    };
}
