<script lang="ts" setup>
import BlogListItem from '@/components/blog/BlogListItem.vue';
import CreateBlogSection from '@/components/blog/CreateBlogSection.vue';
import { Button } from '@/components/ui/button';
import { useBlogForm } from '@/composables/useBlogForm';
import { usePostForm } from '@/composables/usePostForm';
import { useUIState } from '@/composables/useUIState';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Blog, BreadcrumbItem, Category } from '@/types';
import { Head } from '@inertiajs/vue3';

const props = defineProps<{ blogs: Blog[]; canCreate: boolean; categories: Category[] }>();

import { useI18n } from 'vue-i18n';
const { t, locale } = useI18n();
import { ensureNamespace } from '@/i18n'
await ensureNamespace(locale.value, 'blogs')

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('blogs.breadcrumb.dashboard'), href: '/dashboard' },
    { title: t('blogs.breadcrumb.index'), href: '/blogs' },
];

// Use composables for state management
const { showCreate, editingId, createForm, editForm, openCreateForm, closeCreateForm, submitCreate, startEdit, cancelEdit, submitEdit } =
    useBlogForm();

const {
    creatingPostForId,
    editingPostId,
    postForm,
    postEditForm,
    startCreatePost,
    cancelCreatePost,
    submitCreatePost,
    startEditPost,
    cancelEditPost,
    submitEditPost,
} = usePostForm();

const { expandedPostsForId, togglePosts } = useUIState();

// Enhanced functions that coordinate between different composables
function handleStartEdit(blog: Blog) {
    // Hide other forms when starting edit
    creatingPostForId.value = null;
    expandedPostsForId.value = null;
    editingPostId.value = null;
    postForm.reset();
    postEditForm.reset();

    startEdit(blog);
}

function handleStartCreatePost(blog: Blog) {
    // Hide other forms when starting create post
    editingId.value = null;
    expandedPostsForId.value = null;
    editingPostId.value = null;
    editForm.reset();
    postEditForm.reset();

    startCreatePost(blog);
}

function handleTogglePosts(blog: Blog) {
    // Hide other forms when toggling posts
    editingId.value = null;
    creatingPostForId.value = null;
    editingPostId.value = null;
    editForm.reset();
    postForm.reset();
    postEditForm.reset();

    togglePosts(blog);
}

function handleStartEditPost(post: any) {
    // Hide other forms when starting edit post
    editingId.value = null;
    creatingPostForId.value = null;
    editForm.reset();
    postForm.reset();

    startEditPost(post);
}

function handleToggleCreate() {
    if (showCreate.value) {
        closeCreateForm();
    } else {
        openCreateForm();
    }
}
</script>

<template>
    <Head :title="$t('blogs.title')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
            <CreateBlogSection
                :can-create="props.canCreate"
                :categories="props.categories"
                :form="createForm"
                :show-create="showCreate"
                @toggle-create="handleToggleCreate"
                @submit-create="() => submitCreate()"
                @cancel-create="closeCreateForm"
            />

            <!-- Blogs List -->
            <div class="space-y-3">
                <BlogListItem
                    v-for="blog in props.blogs"
                    :key="blog.id"
                    :blog="blog"
                    :categories="props.categories"
                    :edit-form="editForm"
                    :editing-post-id="editingPostId"
                    :is-creating-post="creatingPostForId === blog.id"
                    :is-editing="editingId === blog.id"
                    :is-posts-expanded="expandedPostsForId === blog.id"
                    :post-edit-form="postEditForm"
                    :post-form="postForm"
                    @edit="handleStartEdit"
                    @create-post="handleStartCreatePost"
                    @toggle-posts="handleTogglePosts"
                    @submit-edit="() => submitEdit(blog)"
                    @cancel-edit="cancelEdit"
                    @submit-create-post="() => submitCreatePost()"
                    @cancel-create-post="cancelCreatePost"
                    @edit-post="handleStartEditPost"
                    @submit-edit-post="(post) => submitEditPost(post)"
                    @cancel-edit-post="cancelEditPost"
                />

                <div v-if="props.blogs.length === 0" class="rounded-md border border-dashed p-8 text-center text-sm text-muted-foreground">
                    {{ $t('blogs.empty') }}
                    <span :title="!props.canCreate ? $t('blogs.limit_reached_hint') : ''">
                        <Button
                            :disabled="!props.canCreate"
                            :variant="!props.canCreate ? 'muted' : 'link'"
                            class="ml-2"
                            type="button"
                            @click="openCreateForm"
                        >
                            {{ $t('blogs.empty_cta') }}
                        </Button>
                    </span>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
