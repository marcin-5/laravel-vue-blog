import type { AdminGroup as Group, GroupFormData, InertiaForm } from '@/types/blog.types';
import { createFormDataFromGroup, populateFormFromGroup } from './blogFormUtils';
import { useEntityFormLogic } from './useEntityFormLogic';

export interface UseGroupFormLogicOptions {
    group?: Group;
    isEdit?: boolean;
    externalForm?: InertiaForm<GroupFormData>;
}

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
