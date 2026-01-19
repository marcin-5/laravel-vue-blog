import type { AdminGroup as Group, GroupFormData, InertiaForm } from '@/types/blog.types';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { createFormDataFromGroup, ensureThemeStructure, populateFormFromGroup } from './blogFormUtils';

interface UseGroupFormLogicOptions {
    group?: Group;
    isEdit?: boolean;
    externalForm?: InertiaForm<GroupFormData>;
}

export function useGroupFormLogic(options: UseGroupFormLogicOptions = {}) {
    const { isEdit = false, externalForm } = options;

    const form = externalForm || useForm<GroupFormData>(createFormDataFromGroup(options.group));

    const fieldIdPrefix = computed(() => {
        const base = isEdit ? 'edit-group' : 'create-group';
        const suffix = options.group?.id ?? 'new';
        return `${base}-${suffix}`;
    });

    // Ensure theme structure exists for new forms
    form.theme = ensureThemeStructure(form.theme);

    if (!externalForm) {
        watch(
            () => options.group,
            (newGroup) => {
                if (newGroup) {
                    populateFormFromGroup(form, newGroup);
                }
            },
            { immediate: true },
        );
    }

    return {
        form,
        fieldIdPrefix,
    };
}
