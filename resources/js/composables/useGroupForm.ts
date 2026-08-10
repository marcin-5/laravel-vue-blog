import type { AdminGroup as Group, GroupFormData } from '@/types/blog.types';
import type { Ref } from 'vue';
import { createDefaultGroupFormData, populateFormFromGroup } from './blogFormUtils';
import { useEntityForm } from './useEntityForm';

interface UseGroupFormOptions {
    showCreate?: Ref<boolean>;
}

export function useGroupForm(options: UseGroupFormOptions = {}) {
    return useEntityForm<Group, GroupFormData>({
        createDefaultData: (locale) => createDefaultGroupFormData(locale),
        populateFromEntity: populateFormFromGroup,
        storeRoute: route('blogger.groups.content.store'),
        updateRoute: (id) => route('blogger.groups.content.update', id),
        showCreate: options.showCreate,
    });
}
