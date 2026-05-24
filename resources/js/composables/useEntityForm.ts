import type { InertiaForm } from '@/types/blog.types';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

export interface UseEntityFormOptions<TEntity extends { id: number }, TFormData extends Record<string, any>> {
    createDefaultData: (locale: string) => TFormData;
    populateFromEntity: (form: InertiaForm<TFormData>, entity: TEntity) => void;
    storeRoute: string;
    updateRoute: (id: number) => string;
}

export function useEntityForm<TEntity extends { id: number }, TFormData extends Record<string, any>>(
    options: UseEntityFormOptions<TEntity, TFormData>,
) {
    const showCreate = ref(false);
    const editingId = ref<number | null>(null);
    const { locale } = useI18n();

    const createForm = useForm<TFormData>(options.createDefaultData(locale.value));
    const editForm = useForm<TFormData>(options.createDefaultData(locale.value));

    function openCreateForm() {
        showCreate.value = true;
    }

    function closeCreateForm() {
        showCreate.value = false;
        createForm.reset();
    }

    function submitCreate() {
        createForm.post(options.storeRoute, {
            onSuccess: () => {
                closeCreateForm();
            },
        });
    }

    function startEdit(entity: TEntity) {
        if (editingId.value === entity.id) {
            cancelEdit();
            return;
        }

        editingId.value = entity.id;
        editForm.reset();
        options.populateFromEntity(editForm, entity);
    }

    function cancelEdit() {
        editingId.value = null;
        editForm.reset();
    }

    function submitEdit(entity: TEntity) {
        editForm.patch(options.updateRoute(entity.id), {
            preserveScroll: true,
            onSuccess: () => {
                editingId.value = null;
            },
        });
    }

    return {
        // State
        showCreate,
        editingId,
        createForm,
        editForm,

        // Actions
        openCreateForm,
        closeCreateForm,
        submitCreate,
        startEdit,
        cancelEdit,
        submitEdit,
    };
}
