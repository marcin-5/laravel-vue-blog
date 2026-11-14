import type { InertiaForm } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

export interface UseEntityFormLogicOptions<TEntity, TFormData extends Record<string, any>> {
    entity: () => TEntity | undefined;
    entityType: string;
    isEdit?: boolean;
    externalForm?: InertiaForm<TFormData>;
    createFormData: (entity: TEntity | undefined) => TFormData;
    populateForm: (form: InertiaForm<TFormData>, entity: TEntity) => void;
}

export function useEntityFormLogic<TEntity extends { id?: number }, TFormData extends Record<string, any>>(
    options: UseEntityFormLogicOptions<TEntity, TFormData>,
) {
    const { isEdit = false, externalForm, entityType, createFormData, populateForm } = options;

    const form = externalForm || useForm<TFormData>(createFormData(options.entity()));

    const fieldIdPrefix = computed(() => {
        const base = isEdit ? `edit-${entityType}` : `create-${entityType}`;
        const suffix = options.entity()?.id ?? 'new';
        return `${base}-${suffix}`;
    });

    if (!externalForm) {
        watch(
            options.entity,
            (newEntity) => {
                if (newEntity) {
                    populateForm(form, newEntity);
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
