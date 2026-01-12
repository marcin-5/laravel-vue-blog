<script lang="ts" setup>
import BlogForm from '@/components/blogger/BlogForm.vue';
import PostForm from '@/components/blogger/PostForm.vue';
import PostListItem from '@/components/blogger/PostListItem.vue';
import PublishedBadge from '@/components/blogger/PublishedBadge.vue';
import { Button } from '@/components/ui/button';
import { i18n } from '@/i18n';
import type { AdminBlog as Blog, AdminPostItem as PostItem, Category } from '@/types/blog.types';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

interface Props {
    blog: Blog;
    categories: Category[];
    isEditing: boolean;
    isCreatingPost: boolean;
    isPostsExpanded: boolean;
    editingPostId: number | null;
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

function handleEdit() {
    emit('edit', props.blog);
}

function handleCreatePost() {
    emit('createPost', props.blog);
}

function handleTogglePosts() {
    emit('togglePosts', props.blog);
}

function handleSubmitEdit(form: any) {
    emit('submitEdit', form, props.blog);
}

function handleCancelEdit() {
    emit('cancelEdit');
}

function handleSubmitCreatePost(form: any) {
    emit('submitCreatePost', form);
}

function handleCancelCreatePost() {
    emit('cancelCreatePost');
}

function handleEditPost(post: PostItem) {
    emit('editPost', post);
}

function handleSubmitEditPost(form: any, post: PostItem) {
    emit('submitEditPost', form, post);
}

function handleCancelEditPost() {
    emit('cancelEditPost');
}

function localizedName(name: string | Record<string, string>): string {
    const locale = (i18n.global.locale.value as string) || 'en';
    if (typeof name === 'string') return name;
    return name?.[locale] ?? name?.en ?? Object.values(name ?? {})[0] ?? '';
}
</script>

<template>
    <div class="rounded-md border border-sidebar-border/70 p-4 dark:border-sidebar-border">
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
                        {{ localizedName(cat.name as any) }}
                    </span>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <PublishedBadge :published="blog.is_published" />
                <Button size="sm" type="button" variant="toggle" @click="handleEdit">{{
                    isEditing ? t('blogger.actions.close') : t('blogger.actions.edit')
                }}</Button>
                <Button size="sm" type="button" variant="toggle" @click="handleTogglePosts">
                    <span v-if="isPostsExpanded">{{ t('blogger.actions.hide_posts') }}</span>
                    <span v-else>{{ t('blogger.actions.show_posts') }}</span>
                </Button>
                <Button :variant="isCreatingPost ? 'exit' : 'constructive'" size="sm" type="button" @click="handleCreatePost">{{
                    isCreatingPost ? t('blogger.actions.close') : t('blogger.actions.add_post')
                }}</Button>
            </div>
        </div>

        <!-- Posts List -->
        <div v-if="isPostsExpanded" class="mt-4 ml-4 border-t pt-4">
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
        </div>

        <!-- Inline Edit Form -->
        <BlogForm
            v-if="isEditing"
            :blog="blog"
            :categories="categories"
            :form="props.editForm"
            :id-prefix="`edit-${blog.id}`"
            :is-edit="true"
            @cancel="handleCancelEdit"
            @submit="handleSubmitEdit"
        />

        <!-- Inline Create Post Form -->
        <PostForm
            v-if="isCreatingPost"
            :blog-id="blog.id"
            :form="postForm"
            :id-prefix="`post-${blog.id}`"
            :is-edit="false"
            @cancel="handleCancelCreatePost"
            @submit="handleSubmitCreatePost"
        />
    </div>
</template>
