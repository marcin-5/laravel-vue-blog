<script lang="ts" setup>
import BaseListItem from '@/components/blogger/BaseListItem.vue';
import BlogForm from '@/components/blogger/BlogForm.vue';
import ItemActionGroup from '@/components/blogger/ItemActionGroup.vue';
import PostForm from '@/components/blogger/PostForm.vue';
import PostListItem from '@/components/blogger/PostListItem.vue';
import { useListItemActions } from '@/composables/useListItemActions';
import type { AdminBlog as Blog, AdminPostItem as PostItem, Category } from '@/types/blog.types';
import { localizedName } from '@/utils/localization';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

function handleReload() {
    router.reload({ only: ['blogs'] });
}

interface Props {
    blog: Blog;
    categories: Category[];
    postEditForm?: any; // External post edit form instance
    postForm?: any; // External post form instance for creation
    editForm?: any; // External blog edit form instance
    expandedExtensionsForId?: number | null;
    creatingExtensionId?: number | null;
    editingExtensionId?: number | null;
    extensionForm?: any;
    extensionEditForm?: any;
}

interface Emits {
    (e: 'edit', blog: Blog): void;
    (e: 'createPost', blog: Blog): void;
    (e: 'togglePosts', blog: Blog): void;
    (e: 'submitEdit', form: any, blog: Blog): void;
    (e: 'cancelEdit'): void;
    (e: 'submitCreatePost', form: any): void;
    (e: 'cancelCreatePost'): void;
    (e: 'editPost', post: PostItem): void;
    (e: 'submitEditPost', form: any, post: PostItem): void;
    (e: 'cancelEditPost'): void;
    (e: 'toggleExtensions', post: PostItem): void;
    (e: 'createExtension', post: PostItem): void;
    (e: 'submitCreateExtension', form: any, post: PostItem): void;
    (e: 'cancelCreateExtension'): void;
    (e: 'editExtension', extension: any): void;
    (e: 'submitEditExtension', form: any, extension: any): void;
    (e: 'applyEditExtension', form: any, extension: any): void;
    (e: 'cancelEditExtension'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const {
    isEditing,
    isCreatingPost,
    isPostsExpanded,
    editingPostId,
    handleEdit,
    handleCreatePost,
    handleTogglePosts,
    handleEditPost,
    handleCancelEditPost,
} = useListItemActions<Blog, PostItem>(emit);

function handleSubmitEdit(form: any) {
    emit('submitEdit', form, props.blog);
}

function handleCancelEdit() {
    isEditing.value = false;
    emit('cancelEdit');
}

function handleSubmitCreatePost(form: any) {
    emit('submitCreatePost', form);
}

function handleCancelCreatePost() {
    isCreatingPost.value = false;
    emit('cancelCreatePost');
}

function handleSubmitEditPost(form: any, post: PostItem) {
    emit('submitEditPost', form, post);
}
</script>

<template>
    <BaseListItem
        :is-creating-post="isCreatingPost"
        :is-editing="isEditing"
        :is-posts-expanded="isPostsExpanded"
        :item="blog"
        :subtitle="`/${blog.slug} · ${blog.creation_date ?? ''}`"
    >
        <template #badges>
            <div v-if="blog.categories && blog.categories.length" class="mt-1 flex flex-wrap gap-2">
                <span
                    v-for="cat in blog.categories"
                    :key="`badge-${blog.id}-${cat.id}`"
                    class="rounded bg-muted px-2 py-0.5 text-xs text-muted-foreground"
                >
                    {{ localizedName(cat.name as any) }}
                </span>
            </div>
        </template>

        <template #actions>
            <ItemActionGroup
                :is-creating-post="isCreatingPost"
                :is-editing="isEditing"
                :is-posts-expanded="isPostsExpanded"
                @edit="handleEdit(blog)"
                @create-post="handleCreatePost(blog)"
                @toggle-posts="handleTogglePosts(blog)"
            />
        </template>

        <template #edit-form>
            <BlogForm
                :blog="blog"
                :categories="categories"
                :form="props.editForm"
                :id-prefix="`edit-${blog.id}`"
                :is-edit="true"
                class="mt-4"
                @cancel="handleCancelEdit"
                @submit="handleSubmitEdit"
            />
        </template>

        <template #create-post-form>
            <PostForm
                :blog-id="blog.id"
                :form="postForm"
                :id-prefix="`post-${blog.id}`"
                :is-edit="false"
                class="mt-4"
                @cancel="handleCancelCreatePost"
                @submit="handleSubmitCreatePost"
            />
        </template>

        <template #posts-list>
            <div v-if="blog.posts && blog.posts.length" class="space-y-3">
                <PostListItem
                    v-for="post in blog.posts"
                    :key="`post-${blog.id}-${post.id}`"
                    :creating-extension-id="creatingExtensionId"
                    :edit-form="postEditForm"
                    :editing-extension-id="editingExtensionId"
                    :editing-post-id="editingPostId"
                    :extension-edit-form="extensionEditForm"
                    :extension-form="extensionForm"
                    :is-editing="editingPostId === post.id"
                    :is-extensions-expanded="expandedExtensionsForId === post.id"
                    :post="post"
                    @edit="handleEditPost"
                    @updated="handleReload"
                    @apply-edit-extension="(form, ext) => emit('applyEditExtension', form, ext)"
                    @cancel-create-extension="emit('cancelCreateExtension')"
                    @cancel-edit="handleCancelEditPost"
                    @cancel-edit-extension="emit('cancelEditExtension')"
                    @create-extension="emit('createExtension', $event)"
                    @edit-extension="emit('editExtension', $event)"
                    @submit-create-extension="emit('submitCreateExtension', $event, post)"
                    @submit-edit="handleSubmitEditPost"
                    @submit-edit-extension="(form, ext) => emit('submitEditExtension', form, ext)"
                    @toggle-extensions="emit('toggleExtensions', $event)"
                />
            </div>
            <div v-else class="text-sm text-muted-foreground">{{ t('blogger.posts.empty') }}</div>
        </template>
    </BaseListItem>
</template>
