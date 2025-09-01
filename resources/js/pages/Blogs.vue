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
interface PostItem {
    id: number;
    blog_id: number;
    title: string;
    excerpt: string | null;
    content?: string | null;
    is_published: boolean;
    visibility?: string;
    published_at?: string | null;
    created_at?: string | null;
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
    posts?: PostItem[];
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
    if (editingId.value === blog.id) {
        // Toggle off if this blog is already being edited
        cancelEdit();
        return;
    }
    // Switch to this blog's edit form and ensure the create-post form is hidden
    editingId.value = blog.id;
    creatingPostForId.value = null;
    expandedPostsForId.value = null;
    postForm.reset();

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

// Create Post inline form state
const creatingPostForId = ref<number | null>(null);
const postForm = useForm({
    blog_id: 0 as number,
    title: '' as string,
    excerpt: '' as string,
    content: '' as string,
    is_published: false as boolean,
});

function startCreatePost(blog: Blog) {
    if (creatingPostForId.value === blog.id) {
        // Toggle off if this blog's Create Post form is already open
        cancelCreatePost();
        return;
    }
    // Open this blog's Create Post form and ensure the edit form is hidden
    creatingPostForId.value = blog.id;
    editingId.value = null;
    expandedPostsForId.value = null;
    editForm.reset();

    postForm.reset();
    postForm.blog_id = blog.id;
}

function cancelCreatePost() {
    creatingPostForId.value = null;
    postForm.reset();
}

// Posts list expand/collapse state
const expandedPostsForId = ref<number | null>(null);

function togglePosts(blog: Blog) {
    if (expandedPostsForId.value === blog.id) {
        expandedPostsForId.value = null;
        return;
    }
    // Show posts for this blog and hide other forms
    expandedPostsForId.value = blog.id;
    editingId.value = null;
    creatingPostForId.value = null;
    editForm.reset();
    postForm.reset();
}

// Inline Post Edit state
const editingPostId = ref<number | null>(null);
const postEditForm = useForm({
    title: '' as string,
    excerpt: '' as string | null,
    content: '' as string | null,
    is_published: false as boolean,
});

function startEditPost(post: PostItem) {
    if (editingPostId.value === post.id) {
        cancelEditPost();
        return;
    }
    editingPostId.value = post.id;
    // Hide other forms
    editingId.value = null;
    creatingPostForId.value = null;
    editForm.reset();
    postForm.reset();

    postEditForm.reset();
    postEditForm.title = post.title;
    postEditForm.excerpt = post.excerpt ?? '';
    postEditForm.content = post.content ?? '';
    postEditForm.is_published = !!post.is_published;
}

function cancelEditPost() {
    editingPostId.value = null;
    postEditForm.reset();
}

function submitEditPost(post: PostItem) {
    postEditForm.patch(route('posts.update', post.id), {
        preserveScroll: true,
        onSuccess: () => {
            editingPostId.value = null;
        },
    });
}

function submitCreatePost() {
    // Note: backend route may be defined differently; adjust as needed when API is ready.
    postForm.post(route('posts.store'), {
        preserveScroll: true,
        onSuccess: () => {
            creatingPostForId.value = null;
            postForm.reset();
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
                            <button class="cursor-pointer px-3 py-2" type="button" @click="startCreatePost(blog)">Add Post</button>
                            <button class="cursor-pointer px-3 py-2" type="button" @click="togglePosts(blog)">
                                <span v-if="expandedPostsForId === blog.id">Hide Posts</span>
                                <span v-else>Show Posts</span>
                            </button>
                        </div>
                    </div>

                    <!-- Posts List -->
                    <div v-if="expandedPostsForId === blog.id" class="mt-4 border-t pt-4 ml-4">
                        <div v-if="blog.posts && blog.posts.length" class="space-y-3">
                            <div v-for="post in blog.posts" :key="`post-${blog.id}-${post.id}`" class="rounded-md border p-3">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <div class="text-sm font-medium">{{ post.title }}</div>
                                        <div class="text-xs text-muted-foreground">{{ post.excerpt }}</div>
                                    </div>
                                    <div>
                                        <button class="cursor-pointer px-3 py-2" type="button" @click="startEditPost(post)">
                                            {{ editingPostId === post.id ? 'Close' : 'Edit' }}
                                        </button>
                                    </div>
                                </div>

                                <!-- Inline Post Edit Form -->
                                <div v-if="editingPostId === post.id" class="mt-3 border-t pt-3">
                                    <form class="space-y-3" @submit.prevent="submitEditPost(post)">
                                        <div>
                                            <label :for="`edit-post-title-${post.id}`" class="mb-1 block text-sm font-medium">Title</label>
                                            <input
                                                :id="`edit-post-title-${post.id}`"
                                                v-model="postEditForm.title"
                                                class="block w-full rounded-md border px-3 py-2"
                                                required
                                                type="text"
                                            />
                                            <InputError :message="postEditForm.errors.title" />
                                        </div>
                                        <div>
                                            <label :for="`edit-post-excerpt-${post.id}`" class="mb-1 block text-sm font-medium">Excerpt</label>
                                            <textarea
                                                :id="`edit-post-excerpt-${post.id}`"
                                                v-model="postEditForm.excerpt"
                                                class="block w-full rounded-md border px-3 py-2"
                                                rows="2"
                                            />
                                            <InputError :message="postEditForm.errors.excerpt" />
                                        </div>
                                        <div>
                                            <label :for="`edit-post-content-${post.id}`" class="mb-1 block text-sm font-medium">Content</label>
                                            <textarea
                                                :id="`edit-post-content-${post.id}`"
                                                v-model="postEditForm.content"
                                                class="block w-full rounded-md border px-3 py-2"
                                                rows="4"
                                            />
                                            <InputError :message="postEditForm.errors.content" />
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <input :id="`edit-post-published-${post.id}`" v-model="postEditForm.is_published" type="checkbox" />
                                            <label :for="`edit-post-published-${post.id}`" class="text-sm">Published</label>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <button
                                                :disabled="postEditForm.processing"
                                                class="inline-flex cursor-pointer items-center rounded-md bg-primary px-4 py-2 text-primary-foreground disabled:cursor-not-allowed disabled:opacity-50"
                                                type="submit"
                                            >
                                                {{ postEditForm.processing ? 'Saving…' : 'Save Post' }}
                                            </button>
                                            <button class="cursor-pointer px-3 py-2" type="button" @click="cancelEditPost">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-sm text-muted-foreground">No posts yet.</div>
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

                    <!-- Inline Create Post Form -->
                    <div v-if="creatingPostForId === blog.id" class="mt-4 border-t pt-4">
                        <form class="space-y-4" @submit.prevent="submitCreatePost">
                            <div>
                                <label :for="`post-title-${blog.id}`" class="mb-1 block text-sm font-medium">Post title</label>
                                <input
                                    :id="`post-title-${blog.id}`"
                                    v-model="postForm.title"
                                    class="block w-full rounded-md border px-3 py-2"
                                    required
                                    type="text"
                                    placeholder="My first post"
                                />
                                <InputError :message="postForm.errors.title" />
                            </div>
                            <div>
                                <label :for="`post-excerpt-${blog.id}`" class="mb-1 block text-sm font-medium">Excerpt</label>
                                <textarea
                                    :id="`post-excerpt-${blog.id}`"
                                    v-model="postForm.excerpt"
                                    class="block w-full rounded-md border px-3 py-2"
                                    rows="2"
                                    placeholder="Short summary"
                                />
                                <InputError :message="postForm.errors.excerpt" />
                            </div>
                            <div>
                                <label :for="`post-content-${blog.id}`" class="mb-1 block text-sm font-medium">Content</label>
                                <textarea
                                    :id="`post-content-${blog.id}`"
                                    v-model="postForm.content"
                                    class="block w-full rounded-md border px-3 py-2"
                                    rows="5"
                                    placeholder="Write your post..."
                                />
                                <InputError :message="postForm.errors.content" />
                            </div>
                            <div class="flex items-center gap-2">
                                <input :id="`post-published-${blog.id}`" v-model="postForm.is_published" type="checkbox" />
                                <label :for="`post-published-${blog.id}`" class="text-sm">Publish now</label>
                            </div>

                            <div class="flex items-center gap-2">
                                <button
                                    :disabled="postForm.processing"
                                    class="inline-flex cursor-pointer items-center rounded-md bg-primary px-4 py-2 text-primary-foreground disabled:cursor-not-allowed disabled:opacity-50"
                                    type="submit"
                                >
                                    {{ postForm.processing ? 'Creating…' : 'Create Post' }}
                                </button>
                                <button class="cursor-pointer px-3 py-2" type="button" @click="cancelCreatePost">Cancel</button>
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
