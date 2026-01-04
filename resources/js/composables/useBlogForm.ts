import type { AdminBlog as Blog, BlogFormData } from '@/types/blog.types';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { createDefaultFormData, populateFormFromBlog } from './blogFormUtils';

export function useBlogForm() {
    const showCreate = ref(false);
    const editingId = ref<number | null>(null);
    const { locale } = useI18n();

    const createForm = useForm<BlogFormData>(createDefaultFormData(locale.value));
    const editForm = useForm<BlogFormData>(createDefaultFormData(locale.value));

    function openCreateForm() {
        showCreate.value = true;
    }

    function closeCreateForm() {
        showCreate.value = false;
        createForm.reset();
    }

    function submitCreate() {
        createForm.post(route('blogs.store'), {
            onSuccess: () => {
                closeCreateForm();
            },
        });
    }

    function startEdit(blog: Blog) {
        if (editingId.value === blog.id) {
            cancelEdit();
            return;
        }

        editingId.value = blog.id;
        editForm.reset();
        populateFormFromBlog(editForm, blog);
    }

    function cancelEdit() {
        editingId.value = null;
        editForm.reset();
    }

    function submitEdit(blog: Blog) {
        editForm.patch(route('blogs.update', blog.id), {
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
