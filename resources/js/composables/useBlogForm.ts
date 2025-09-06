import type { Blog } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

export function useBlogForm() {
    const showCreate = ref(false);
    const editingId = ref<number | null>(null);

    const createForm = useForm({
        name: '',
        description: null as string | null,
        categories: [] as number[],
        sidebar: 0 as number,
        page_size: 10 as number,
    });

    const editForm = useForm({
        name: '',
        description: null as string | null,
        is_published: false as boolean,
        locale: 'en' as string,
        categories: [] as number[],
        sidebar: 0 as number,
        page_size: 10 as number,
    });

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
            // Toggle off if this blog is already being edited
            cancelEdit();
            return;
        }

        editingId.value = blog.id;
        editForm.reset();
        editForm.name = blog.name;
        editForm.description = blog.description;
        editForm.is_published = blog.is_published;
        editForm.locale = (blog.locale as string) || 'en';
        editForm.sidebar = (blog.sidebar as number) ?? 0;
        editForm.page_size = (blog.page_size as number) ?? 10;
        editForm.categories = (blog.categories ?? []).map((c) => c.id);
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
