import type { UseGroupFormLogicOptions } from '@/types/blog.types';
import { createFormDataFromGroup, populateFormFromGroup } from './blogFormUtils';
import { useEntityFormLogic } from './useEntityFormLogic';

export function useGroupFormLogic(options: UseGroupFormLogicOptions = {}) {
    return useEntityFormLogic({
        entity: () => options.group,
        entityType: 'group',
        isEdit: options.isEdit,
        externalForm: options.externalForm,
        createFormData: (entity) => createFormDataFromGroup(entity),
        populateForm: populateFormFromGroup,
    });
}
