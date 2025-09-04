<script lang="ts" setup>
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface CategoryRow {
    id: number;
    name: string;
    slug: string;
    blogs_count?: number;
}

const props = defineProps<{ categories: CategoryRow[] }>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Categories', href: '/admin/categories' }];

// Create form
const createForm = useForm({
    name: '' as string,
});

function submitCreate() {
    createForm.post(route('admin.categories.store'), {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
        },
    });
}

// Edit handling
const editingId = ref<number | null>(null);
const editForm = useForm({
    name: '' as string,
});

function startEdit(cat: CategoryRow) {
    editingId.value = cat.id;
    editForm.reset();
    editForm.name = cat.name;
}

function cancelEdit() {
    editingId.value = null;
    editForm.reset();
}

function submitEdit(cat: CategoryRow) {
    editForm.patch(route('admin.categories.update', cat.id), {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;
        },
    });
}

function destroy(cat: CategoryRow) {
    if (!confirm(`Delete category "${cat.name}"? This will remove it from all blogs.`)) return;
    router.delete(route('admin.categories.destroy', cat.id), { preserveScroll: true });
}

const sortedCategories = computed(() => [...props.categories].sort((a, b) => a.name.localeCompare(b.name)));
</script>

<template>
    <Head title="Categories" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
            <div class="rounded-md border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                <h2 class="mb-3 text-lg font-semibold">Create Category</h2>
                <form class="flex flex-wrap items-end gap-3" @submit.prevent="submitCreate">
                    <div class="min-w-64">
                        <label class="mb-1 block text-sm font-medium" for="new-name">Name</label>
                        <input id="new-name" v-model="createForm.name" class="block w-full rounded-md border px-3 py-2" required type="text" />
                        <InputError :message="createForm.errors.name" />
                    </div>
                    <div>
                        <Button :disabled="createForm.processing" type="submit" variant="constructive">
                            {{ createForm.processing ? 'Creating…' : 'Create' }}
                        </Button>
                    </div>
                </form>
            </div>

            <div class="rounded-md border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                <h2 class="mb-3 text-lg font-semibold">Categories</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-sidebar-border/70 text-xs text-muted-foreground uppercase dark:border-sidebar-border">
                            <tr>
                                <th class="py-2 pr-4">Name</th>
                                <th class="py-2 pr-4">Slug</th>
                                <th class="py-2 pr-4">Blogs</th>
                                <th class="py-2 pr-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="cat in sortedCategories"
                                :key="cat.id"
                                class="border-b border-sidebar-border/70 last:border-b-0 dark:border-sidebar-border"
                            >
                                <td class="py-2 pr-4">
                                    <div v-if="editingId !== cat.id">{{ cat.name }}</div>
                                    <div v-else>
                                        <input
                                            :id="`edit-name-${cat.id}`"
                                            v-model="editForm.name"
                                            class="w-full rounded-md border px-2 py-1"
                                            required
                                            type="text"
                                        />
                                        <InputError :message="editForm.errors.name" />
                                    </div>
                                </td>
                                <td class="py-2 pr-4">
                                    <code class="text-xs">{{ cat.slug }}</code>
                                </td>
                                <td class="py-2 pr-4">{{ cat.blogs_count ?? 0 }}</td>
                                <td class="py-2 pr-4">
                                    <div v-if="editingId !== cat.id" class="flex gap-2">
                                        <Button size="sm" type="button" variant="secondary" @click="startEdit(cat)">Edit</Button>
                                        <Button size="sm" type="button" variant="destructive" @click="destroy(cat)">Delete</Button>
                                    </div>
                                    <div v-else class="flex gap-2">
                                        <Button
                                            :disabled="editForm.processing"
                                            size="sm"
                                            type="button"
                                            variant="constructive"
                                            @click="submitEdit(cat)"
                                        >
                                            {{ editForm.processing ? 'Saving…' : 'Save' }}
                                        </Button>
                                        <Button size="sm" type="button" variant="destructive" @click="cancelEdit">Cancel</Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
