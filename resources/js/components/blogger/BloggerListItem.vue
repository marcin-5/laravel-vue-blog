<script generic="T extends ManageableItem, P extends AdminPostItem" lang="ts" setup>
import BaseListItem from '@/components/blogger/BaseListItem.vue';
import PostListItem from '@/components/blogger/PostListItem.vue';
import { useListItemActions } from '@/composables/useListItemActions';
import type { AdminPostItem, ListItemEmits, ListItemProps, ManageableItem } from '@/types/blog.types';
import { watch } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<ListItemProps<T> & { subtitle?: string }>();
const emit = defineEmits<ListItemEmits<T, P>>();

const { t } = useI18n();

const {
    isEditing: localIsEditing,
    isCreatingPost: localIsCreatingPost,
    isPostsExpanded: localIsPostsExpanded,
    editingPostId: localEditingPostId,
    handleEdit,
    handleCreatePost,
    handleTogglePosts,
    handleEditPost,
    handleCancelEditPost,
} = useListItemActions<T, P>(emit as any);

watch(
    () => props.isEditing,
    (val) => {
        if (val !== undefined) localIsEditing.value = val;
    },
    { immediate: true },
);

watch(
    () => props.isCreatingPost,
    (val) => {
        if (val !== undefined) localIsCreatingPost.value = val;
    },
    { immediate: true },
);

watch(
    () => props.isPostsExpanded,
    (val) => {
        if (val !== undefined) localIsPostsExpanded.value = val;
    },
    { immediate: true },
);

watch(
    () => props.editingPostId,
    (val) => {
        if (val !== undefined) localEditingPostId.value = val;
    },
    { immediate: true },
);

function handleCancelEdit() {
    localIsEditing.value = false;
    emit('cancelEdit');
}

function handleCancelCreatePost() {
    localIsCreatingPost.value = false;
    emit('cancelCreatePost');
}

function handlePostUpdated() {
    emit('postUpdated');
}
</script>

<template>
    <BaseListItem
        :is-creating-post="localIsCreatingPost"
        :is-editing="localIsEditing"
        :is-posts-expanded="localIsPostsExpanded"
        :item="item"
        :subtitle="subtitle"
    >
        <template #badges>
            <slot name="badges" />
        </template>

        <template #actions>
            <slot
                :handle-create-post="handleCreatePost"
                :handle-edit="handleEdit"
                :handle-toggle-posts="handleTogglePosts"
                :is-creating-post="localIsCreatingPost"
                :is-editing="localIsEditing"
                :is-posts-expanded="localIsPostsExpanded"
                name="actions"
            />
        </template>

        <template #edit-form>
            <slot :handle-cancel-edit="handleCancelEdit" name="edit-form" />
        </template>

        <template #create-post-form>
            <slot :handle-cancel-create-post="handleCancelCreatePost" name="create-post-form" />
        </template>

        <template #posts-list>
            <slot name="posts-header">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-medium">{{ t('blogger.posts_list_title') }} ({{ (item as any).posts?.length || 0 }})</h3>
                </div>
            </slot>

            <div v-if="(item as any).posts && (item as any).posts.length > 0" class="mt-4 space-y-3">
                <PostListItem
                    v-for="post in (item as any).posts"
                    :key="post.id"
                    :creating-extension-id="creatingExtensionId"
                    :edit-form="postEditForm"
                    :editing-extension-id="editingExtensionId"
                    :editing-post-id="localEditingPostId"
                    :extension-edit-form="extensionEditForm"
                    :extension-form="extensionForm"
                    :is-editing="localEditingPostId === post.id"
                    :is-extensions-expanded="expandedExtensionsForId === post.id"
                    :post="post"
                    @edit="(p: AdminPostItem) => handleEditPost(p as P)"
                    @updated="handlePostUpdated"
                    @apply-edit-extension="(form: any, ext: any) => emit('applyEditExtension', form, ext)"
                    @cancel-create-extension="emit('cancelCreateExtension')"
                    @cancel-edit="handleCancelEditPost"
                    @cancel-edit-extension="emit('cancelEditExtension')"
                    @create-extension="(p: AdminPostItem) => emit('createExtension', p as P)"
                    @edit-extension="emit('editExtension', $event)"
                    @submit-create-extension="(form: any, p: AdminPostItem) => emit('submitCreateExtension', form, p as P)"
                    @submit-edit="(form: any, p: AdminPostItem) => emit('submitEditPost', form, p as P)"
                    @submit-edit-extension="(form: any, ext: any) => emit('submitEditExtension', form, ext)"
                    @toggle-extensions="(p: AdminPostItem) => emit('toggleExtensions', p as P)"
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
