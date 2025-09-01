<script lang="ts" setup>
import InputError from '@/components/InputError.vue';
import PublishedBadge from '@/components/PublishedBadge.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Category {
    id: number;
    name: string;
}
interface Blog {
    id: number;
    user_id: number;
    name: string;
    slug: string;
    description: string | null;
    is_published: boolean;
    creation_date?: string | null;
    categories?: Category[];
}
const props = defineProps<{ blogs: Blog[]; canCreate: boolean; categories: Category[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Blogs', href: '/blogs' },
];

const showCreate = ref(false);
const createForm = useForm({
    name: '',
    description: null as string | null,
    categories: [] as number[],
});

function submitCreate() {
    createForm.post(route('blogs.store'), {
        onSuccess: () => {
            showCreate.value = false;
            createForm.reset();
        },
    });
}

const editingId = ref<number | null>(null);
const editForm = useForm({
    name: '',
    description: null as string | null,
    is_published: false as boolean,
    categories: [] as number[],
});

function startEdit(blog: Blog) {
    editingId.value = blog.id;
    editForm.reset();
    editForm.name = blog.name;
    editForm.description = blog.description;
    editForm.is_published = blog.is_published;
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
</script>

<template>
    <Head title="Blogs" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold">Your Blogs</h1>
                <div :title="!props.canCreate ? 'Maximum number of blogs reached. Please ask an admin to increase your blog quota.' : ''">
                    <button
                        :disabled="!props.canCreate"
                        class="inline-flex cursor-pointer items-center rounded-md bg-primary px-4 py-2 text-primary-foreground disabled:cursor-not-allowed disabled:opacity-50"
                        type="button"
                        @click="showCreate = !showCreate"
                    >
                        <span v-if="showCreate">Close</span>
                        <span v-else>Create New Blog</span>
                    </button>
                </div>
            </div>

            <!-- Create New Blog Form -->
            <div v-if="showCreate" class="rounded-md border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                <form class="space-y-4" @submit.prevent="submitCreate">
                    <div>
                        <label class="mb-1 block text-sm font-medium" for="new-name">Name</label>
                        <input
                            id="new-name"
                            v-model="createForm.name"
                            class="block w-full rounded-md border px-3 py-2"
                            placeholder="My Awesome Blog"
                            required
                            type="text"
                        />
                        <InputError :message="createForm.errors.name" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium" for="new-description">Description</label>
                        <textarea
                            id="new-description"
                            v-model="createForm.description"
                            class="block w-full rounded-md border px-3 py-2"
                            placeholder="What's this blog about?"
                            rows="3"
                        />
                        <InputError :message="createForm.errors.description" />
                    </div>
                    <div>
                        <div class="mb-1 block text-sm font-medium">Categories</div>
                        <div class="flex flex-wrap gap-3">
                            <label v-for="cat in props.categories" :key="`new-cat-${cat.id}`" class="inline-flex items-center gap-2">
                                <input v-model="createForm.categories" :value="cat.id" type="checkbox" />
                                <span class="text-sm">{{ cat.name }}</span>
                            </label>
                        </div>
                        <InputError :message="createForm.errors.categories" />
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            :disabled="createForm.processing"
                            class="inline-flex cursor-pointer items-center rounded-md bg-primary px-4 py-2 text-primary-foreground disabled:cursor-not-allowed disabled:opacity-50"
                            type="submit"
                        >
                            {{ createForm.processing ? 'Creating…' : 'Create' }}
                        </button>
                        <button class="cursor-pointer px-3 py-2" type="button" @click="showCreate = false">Cancel</button>
                    </div>
                </form>
            </div>

            <!-- Blogs List -->
            <div class="space-y-3">
                <div v-for="blog in props.blogs" :key="blog.id" class="rounded-md border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="text-base font-medium">{{ blog.name }}</div>
                            <div class="text-xs text-muted-foreground">/{{ blog.slug }} · {{ blog.creation_date ?? '' }}</div>
                            <div v-if="blog.categories && blog.categories.length" class="mt-1 flex flex-wrap gap-2">
                                <span
                                    v-for="cat in blog.categories"
                                    :key="`badge-${blog.id}-${cat.id}`"
                                    class="rounded bg-muted px-2 py-0.5 text-xs text-muted-foreground"
                                >
                                    {{ cat.name }}
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <PublishedBadge :published="blog.is_published" />
                            <button class="cursor-pointer px-3 py-2" type="button" @click="startEdit(blog)">Edit</button>
                        </div>
                    </div>

                    <!-- Inline Edit Form -->
                    <div v-if="editingId === blog.id" class="mt-4 border-t pt-4">
                        <form class="space-y-4" @submit.prevent="submitEdit(blog)">
                            <div>
                                <label :for="`edit-name-${blog.id}`" class="mb-1 block text-sm font-medium">Name</label>
                                <input
                                    :id="`edit-name-${blog.id}`"
                                    v-model="editForm.name"
                                    class="block w-full rounded-md border px-3 py-2"
                                    required
                                    type="text"
                                />
                                <InputError :message="editForm.errors.name" />
                            </div>
                            <div>
                                <label :for="`edit-description-${blog.id}`" class="mb-1 block text-sm font-medium">Description</label>
                                <textarea
                                    :id="`edit-description-${blog.id}`"
                                    v-model="editForm.description"
                                    class="block w-full rounded-md border px-3 py-2"
                                    rows="3"
                                />
                                <InputError :message="editForm.errors.description" />
                            </div>
                            <!-- Move Published checkbox up to be below the name/slug area -->
                            <div class="flex items-center gap-2">
                                <input :id="`edit-published-${blog.id}`" v-model="editForm.is_published" type="checkbox" />
                                <label :for="`edit-published-${blog.id}`" class="text-sm">Published</label>
                                <span class="text-xs text-muted-foreground">/{{ blog.slug }}</span>
                            </div>
                            <InputError :message="editForm.errors.is_published" />
                            <div>
                                <div class="mb-1 block text-sm font-medium">Categories</div>
                                <div class="flex flex-wrap gap-3">
                                    <label
                                        v-for="cat in props.categories"
                                        :key="`edit-cat-${blog.id}-${cat.id}`"
                                        class="inline-flex items-center gap-2"
                                    >
                                        <input v-model="editForm.categories" :value="cat.id" type="checkbox" />
                                        <span class="text-sm">{{ cat.name }}</span>
                                    </label>
                                </div>
                                <InputError :message="editForm.errors.categories" />
                            </div>

                            <div class="flex items-center gap-2">
                                <button
                                    :disabled="editForm.processing"
                                    class="inline-flex cursor-pointer items-center rounded-md bg-primary px-4 py-2 text-primary-foreground disabled:cursor-not-allowed disabled:opacity-50"
                                    type="submit"
                                >
                                    {{ editForm.processing ? 'Saving…' : 'Save' }}
                                </button>
                                <button class="cursor-pointer px-3 py-2" type="button" @click="cancelEdit">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div v-if="props.blogs.length === 0" class="rounded-md border border-dashed p-8 text-center text-sm text-muted-foreground">
                    You have no blogs yet.
                    <span :title="!props.canCreate ? 'Maximum number of blogs reached. Please ask an admin to increase your blog quota.' : ''">
                        <button
                            :disabled="!props.canCreate"
                            class="ml-2 cursor-pointer underline disabled:cursor-not-allowed"
                            type="button"
                            @click="showCreate = true"
                        >
                            Create your first blog
                        </button>
                    </span>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
