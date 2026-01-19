import type { AdminGroup as Group, GroupFormData } from '@/types/blog.types';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { createDefaultGroupFormData, populateFormFromGroup } from './blogFormUtils';

export function useGroupForm() {
    const showCreate = ref(false);
    const editingId = ref<number | null>(null);
    const { locale } = useI18n();

    const createForm = useForm<GroupFormData>(createDefaultGroupFormData(locale.value));
    const editForm = useForm<GroupFormData>(createDefaultGroupFormData(locale.value));

    function openCreateForm() {
        showCreate.value = true;
    }

    function closeCreateForm() {
        showCreate.value = false;
        createForm.reset();
    }

    function submitCreate() {
        createForm.post(route('groups.store'), {
            onSuccess: () => {
                closeCreateForm();
            },
        });
    }

    function startEdit(group: Group) {
        if (editingId.value === group.id) {
            cancelEdit();
            return;
        }

        editingId.value = group.id;
        editForm.reset();
        populateFormFromGroup(editForm, group);
    }

    function cancelEdit() {
        editingId.value = null;
        editForm.reset();
    }

    function submitEdit(group: Group) {
        editForm.patch(route('groups.update', group.id), {
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
