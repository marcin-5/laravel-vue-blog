<script lang="ts" setup>
import BlogForm from '@/components/blogger/BlogForm.vue';
import BlogListItem from '@/components/blogger/BlogListItem.vue';
import CreateEntitySection from '@/components/blogger/CreateEntitySection.vue';
import { Button } from '@/components/ui/button';
import { useBlogForm } from '@/composables/useBlogForm';
import { usePostForm } from '@/composables/usePostForm';
import { useUIState } from '@/composables/useUIState';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { AdminBlog as Blog, Category } from '@/types/blog.types';
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const props = defineProps<{ blogs: Blog[]; canCreate: boolean; categories: Category[] }>();

const { t } = useI18n();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('blogger.breadcrumb.dashboard'), href: '/dashboard' },
    { title: t('blogger.breadcrumb.index'), href: '/blogs' },
];

// Use composables for state management
const { showCreate, editingId, createForm, editForm, openCreateForm, closeCreateForm, submitCreate, startEdit, cancelEdit, submitEdit } =
    useBlogForm();

const {
    creatingPostForId,
    editingPostId,
    creatingExtensionForId,
    editingExtensionId,
    postForm,
    postEditForm,
    extensionForm,
    extensionEditForm,
    startCreatePost,
    cancelCreatePost,
    submitCreatePost,
    startEditPost,
    cancelEditPost,
    submitEditPost,
    startCreateExtension,
    cancelCreateExtension,
    submitCreateExtension,
    startEditExtension,
    cancelEditExtension,
    submitEditExtension,
} = usePostForm();

const { expandedPostsForId, expandedExtensionsForId, togglePosts, toggleExtensions } = useUIState();

// Enhanced functions that coordinate between different composables
function handleStartEdit(blog: Blog) {
    // Hide other forms when starting edit
    creatingPostForId.value = null;
    expandedPostsForId.value = null;
    editingPostId.value = null;
    expandedExtensionsForId.value = null;
    creatingExtensionForId.value = null;
    editingExtensionId.value = null;
    postForm.reset();
    postEditForm.reset();

    startEdit(blog);
}

function handleStartCreatePost(blog: Blog) {
    // Hide other forms when starting create post
    editingId.value = null;
    expandedPostsForId.value = null;
    editingPostId.value = null;
    expandedExtensionsForId.value = null;
    creatingExtensionForId.value = null;
    editingExtensionId.value = null;
    editForm.reset();
    postEditForm.reset();

    startCreatePost(blog);
}

function handleTogglePosts(blog: Blog) {
    // Hide other forms when toggling posts
    editingId.value = null;
    creatingPostForId.value = null;
    editingPostId.value = null;
    expandedExtensionsForId.value = null;
    creatingExtensionForId.value = null;
    editingExtensionId.value = null;
    editForm.reset();
    postForm.reset();
    postEditForm.reset();

    togglePosts(blog);
}

function handleStartEditPost(post: any) {
    // Hide other forms when starting edit post
    editingId.value = null;
    creatingPostForId.value = null;
    expandedExtensionsForId.value = null;
    creatingExtensionForId.value = null;
    editingExtensionId.value = null;
    editForm.reset();
    postForm.reset();

    startEditPost(post);
}

function handleToggleExtensions(post: any) {
    editingId.value = null;
    creatingPostForId.value = null;
    editingPostId.value = null;
    creatingExtensionForId.value = null;
    editingExtensionId.value = null;

    toggleExtensions(post);
}

function handleStartCreateExtension(post: any) {
    editingId.value = null;
    creatingPostForId.value = null;
    editingPostId.value = null;
    expandedExtensionsForId.value = post.id; // Expand list to show create form
    editingExtensionId.value = null;

    startCreateExtension(post);
}

function handleStartEditExtension(extension: any) {
    editingId.value = null;
    creatingPostForId.value = null;
    editingPostId.value = null;
    creatingExtensionForId.value = null;

    startEditExtension(extension);
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
    <Head :title="$t('blogger.title')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
            <CreateEntitySection
                :can-create="props.canCreate"
                :form="createForm"
                :show-create="showCreate"
                :title="t('blogger.create_section.title')"
                :tooltip-close="t('blogger.create_section.close_button')"
                :tooltip-create="t('blogger.create_section.create_button')"
                :tooltip-limit="t('blogger.create_section.quota_reached_tooltip')"
                @cancel="closeCreateForm"
                @submit="() => submitCreate()"
                @toggle="handleToggleCreate"
            >
                <template #form="{ form, onCancel, onSubmit }">
                    <BlogForm :categories="props.categories" :form="form" :is-edit="false" id-prefix="new" @cancel="onCancel" @submit="onSubmit" />
                </template>
            </CreateEntitySection>

            <!-- Blogs List -->
            <div class="space-y-3">
                <BlogListItem
                    v-for="blog in props.blogs"
                    :key="blog.id"
                    :blog="blog"
                    :categories="props.categories"
                    :creating-extension-id="creatingExtensionForId"
                    :edit-form="editForm"
                    :editing-extension-id="editingExtensionId"
                    :editing-post-id="editingPostId"
                    :expanded-extensions-for-id="expandedExtensionsForId"
                    :extension-edit-form="extensionEditForm"
                    :extension-form="extensionForm"
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
                    @submit-edit-post="() => submitEditPost()"
                    @cancel-edit-post="cancelEditPost"
                    @toggle-extensions="handleToggleExtensions"
                    @create-extension="handleStartCreateExtension"
                    @submit-create-extension="($event, post) => submitCreateExtension(post)"
                    @cancel-create-extension="cancelCreateExtension"
                    @edit-extension="handleStartEditExtension"
                    @submit-edit-extension="(form, ext) => submitEditExtension(ext)"
                    @apply-edit-extension="(form, ext) => submitEditExtension(ext, false)"
                    @cancel-edit-extension="cancelEditExtension"
                />

                <div v-if="props.blogs.length === 0" class="rounded-md border border-dashed p-8 text-center text-sm text-muted-foreground">
                    {{ $t('blogger.empty') }}
                    <span :title="!props.canCreate ? $t('blogger.limit_reached_hint') : ''">
                        <Button
                            :disabled="!props.canCreate"
                            :variant="!props.canCreate ? 'muted' : 'link'"
                            class="ml-2"
                            type="button"
                            @click="openCreateForm"
                        >
                            {{ $t('blogger.empty_cta') }}
                        </Button>
                    </span>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
