import type { UseBlogFormLogicOptions } from '@/types/blog.types';
import { createFormDataFromBlog, populateFormFromBlog } from './blogFormUtils';
import { useEntityFormLogic } from './useEntityFormLogic';

export function useBlogFormLogic(options: UseBlogFormLogicOptions = {}) {
    const { form, fieldIdPrefix } = useEntityFormLogic({
        entity: () => options.blog,
        entityType: 'blog',
        isEdit: options.isEdit,
        externalForm: options.externalForm,
        createFormData: (entity) => createFormDataFromBlog(entity),
        populateForm: populateFormFromBlog,
    });

    const updateCategories = (categoryIds: number[]) => {
        form.categories = categoryIds;
    };

    return {
        form,
        fieldIdPrefix,
        updateCategories,
    };
}
