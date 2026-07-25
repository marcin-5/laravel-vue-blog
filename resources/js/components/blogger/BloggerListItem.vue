<script generic="T extends ManageableItem, P extends AdminPostItem" lang="ts" setup>
import BaseListItem from '@/components/blogger/BaseListItem.vue';
import PostListItem from '@/components/blogger/PostListItem.vue';
import type { AdminPostItem, BloggerItemContext, ManageableItem } from '@/types/blog.types';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    context: BloggerItemContext<T, P>;
}>();
const emit = defineEmits<{
    postUpdated: [];
}>();

const { t } = useI18n();
const actions = props.context.actions;
const handleEdit = () => actions.edit();
const handleCreatePost = () => actions.createPost();
const handleTogglePosts = () => actions.togglePosts();
const handleEditPost = (post: AdminPostItem) => actions.editPost(post as P);
const handleToggleExtensions = (post: AdminPostItem) => actions.toggleExtensions(post as P);
</script>

<template>
    <BaseListItem
        :is-creating-post="context.isCreatingPost"
        :is-editing="context.isEditing"
        :is-posts-expanded="context.isPostsExpanded"
        :item="context.item"
        :subtitle="context.subtitle"
    >
        <template #badges>
            <slot name="badges" />
        </template>

        <template #actions>
            <slot
                :handle-create-post="handleCreatePost"
                :handle-edit="handleEdit"
                :handle-toggle-posts="handleTogglePosts"
                :is-creating-post="context.isCreatingPost"
                :is-editing="context.isEditing"
                :is-posts-expanded="context.isPostsExpanded"
                name="actions"
            />
        </template>

        <template #edit-form>
            <slot :handle-cancel-edit="context.actions.cancelEdit" name="edit-form" />
        </template>

        <template #create-post-form>
            <slot :handle-cancel-create-post="context.actions.cancelCreatePost" name="create-post-form" />
        </template>

        <template #posts-list>
            <slot name="posts-header">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-medium">{{ t('blogger.posts_list_title') }} ({{ context.item.posts?.length || 0 }})</h3>
                </div>
            </slot>

            <div v-if="context.item.posts && context.item.posts.length > 0" class="mt-4 space-y-3">
                <PostListItem
                    v-for="post in context.item.posts"
                    :key="post.id"
                    :edit-form="context.editForm"
                    :is-editing="context.editingPostId === post.id"
                    :is-extensions-expanded="context.expandedExtensionsForId === post.id"
                    :post="post"
                    @edit="handleEditPost"
                    @updated="emit('postUpdated')"
                    @cancel-edit="context.actions.cancelEditPost"
                    @submit-edit="context.actions.submitEditPost"
                    @toggle-extensions="handleToggleExtensions"
                />
            </div>
            <div v-else class="py-4 text-center text-sm text-muted-foreground">
                <slot name="no-posts">
                    {{ t('blogger.no_posts') }}
                </slot>
            </div>
        </template>
    </BaseListItem>
</template>
