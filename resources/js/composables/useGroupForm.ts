import type { AdminGroup as Group, GroupFormData } from '@/types/blog.types';
import { createDefaultGroupFormData, populateFormFromGroup } from './blogFormUtils';
import { useEntityForm } from './useEntityForm';

export function useGroupForm() {
    return useEntityForm<Group, GroupFormData>({
        createDefaultData: (locale) => createDefaultGroupFormData(locale),
        populateFromEntity: populateFormFromGroup,
        storeRoute: route('blogger.groups.content.store'),
        updateRoute: (id) => route('blogger.groups.content.update', id),
    });
}
