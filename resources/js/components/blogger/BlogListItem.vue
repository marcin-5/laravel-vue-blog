<script lang="ts" setup>
import BlogForm from '@/components/blogger/BlogForm.vue';
import BloggerListItem from '@/components/blogger/BloggerListItem.vue';
import ItemActionGroup from '@/components/blogger/ItemActionGroup.vue';
import PostForm from '@/components/blogger/PostForm.vue';
import { useBlogItemState } from '@/composables/useBlogItemState';
import type { AdminBlog as Blog, AdminPostItem as PostItem, BloggerItemContext, Category } from '@/types/blog.types';
import { localizedName } from '@/utils/localization';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

interface Props {
    item: Blog;
    categories: Category[];
}

const props = defineProps<Props>();
const { t } = useI18n();
const state = useBlogItemState(props.item);
const {
    editForm,
    editingPostId,
    expandedExtensionsForId,
    isCreatingPost,
    isEditing,
    isPostsExpanded,
    postForm: rawPostForm,
    cancelCreateExtension,
    cancelCreatePost,
    cancelEdit,
    cancelEditExtension,
    cancelEditPost,
    createPost,
    edit,
    editPost,
    startCreateExtension,
    startEditExtension,
    submitCreateExtension,
    submitCreatePost,
    submitEdit,
    submitEditExtension,
    submitEditPost,
    toggleExtensions,
    togglePosts,
} = state;
const postForm = rawPostForm as any;
const context = computed<BloggerItemContext<Blog, PostItem>>(() => ({
    item: props.item,
    subtitle: `/${props.item.slug} · ${props.item.creation_date ?? ''}`,
    isEditing: isEditing.value,
    isCreatingPost: isCreatingPost.value,
    isPostsExpanded: isPostsExpanded.value,
    editingPostId: editingPostId.value,
    expandedExtensionsForId: expandedExtensionsForId.value,
    editForm,
    actions: {
        edit,
        createPost,
        togglePosts,
        editPost,
        toggleExtensions,
        cancelEdit,
        cancelCreatePost,
        submitEdit,
        submitCreatePost,
        cancelEditPost,
        submitEditPost,
        createExtension: startCreateExtension,
        submitCreateExtension,
        cancelCreateExtension,
        editExtension: startEditExtension,
        submitEditExtension,
        cancelEditExtension,
    },
}));

function handleReload() {
    router.reload({ only: ['blogs'] });
}
</script>

<template>
    <BloggerListItem :context="context" @post-updated="handleReload">
        <template #badges>
            <div v-if="item.categories && item.categories.length" class="mt-1 flex flex-wrap gap-2">
                <span
                    v-for="cat in item.categories"
                    :key="`badge-${item.id}-${cat.id}`"
                    class="rounded bg-muted px-2 py-0.5 text-xs text-muted-foreground"
                >
                    {{ localizedName(cat.name as any) }}
                </span>
            </div>
        </template>

        <template #actions="{ handleEdit, handleCreatePost, handleTogglePosts, isCreatingPost, isEditing, isPostsExpanded }">
            <ItemActionGroup
                :is-creating-post="isCreatingPost"
                :is-editing="isEditing"
                :is-posts-expanded="isPostsExpanded"
                @edit="handleEdit"
                @create-post="handleCreatePost"
                @toggle-posts="handleTogglePosts"
            />
        </template>

        <template #edit-form="{ handleCancelEdit }">
            <BlogForm
                :blog="item"
                :categories="categories"
                :form="editForm"
                :id-prefix="`edit-${item.id}`"
                :is-edit="true"
                class="mt-4"
                @cancel="handleCancelEdit"
                @submit="submitEdit"
            />
        </template>

        <template #create-post-form="">
            <PostForm
                :blog-id="item.id"
                :form="postForm"
                :id-prefix="`post-${item.id}`"
                :is-edit="false"
                class="mt-4"
                @cancel="cancelCreatePost"
                @submit="submitCreatePost"
            />
        </template>

        <template #no-posts>
            {{ t('blogger.posts.empty') }}
        </template>
    </BloggerListItem>
</template>
