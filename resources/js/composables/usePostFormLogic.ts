import type { UsePostFormLogicOptions } from '@/types/blog.types';
import { computed, watch } from 'vue';
import { createFormDataFromPost, populateFormFromPost } from './blogFormUtils';
import { useEntityFormLogic } from './useEntityFormLogic';

export function usePostFormLogic(options: UsePostFormLogicOptions = {}) {
    const { isEdit = false, externalForm } = options;

    const { form, fieldIdPrefix: baseFieldIdPrefix } = useEntityFormLogic({
        entity: () => options.post,
        entityType: 'post',
        isEdit,
        externalForm,
        createFormData: (entity) => createFormDataFromPost(entity, options.blogId),
        populateForm: populateFormFromPost,
    });

    const fieldIdPrefix = computed(() => {
        if (!isEdit && !options.post && options.blogId) {
            return `create-post-${options.blogId}`;
        }
        return baseFieldIdPrefix.value;
    });

    if (!externalForm) {
        watch(
            () => options.blogId,
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
        updateFormFromPost: (post: any) => populateFormFromPost(form, post),
    };
}
