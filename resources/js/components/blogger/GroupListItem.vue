<script lang="ts" setup>
import BaseListItem from '@/components/blogger/BaseListItem.vue';
import GroupForm from '@/components/blogger/GroupForm.vue';
import ItemActionGroup from '@/components/blogger/ItemActionGroup.vue';
import PostForm from '@/components/blogger/PostForm.vue';
import PostListItem from '@/components/blogger/PostListItem.vue';
import { Badge } from '@/components/ui/badge';
import { TooltipButton } from '@/components/ui/tooltip';
import { useListItemActions } from '@/composables/useListItemActions';
import type { AdminGroup as Group, AdminPostItem as PostItem } from '@/types/blog.types';
import { router } from '@inertiajs/vue3';
import { watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

interface Props {
    group: Group;
    postEditForm?: any;
    postForm?: any;
    editForm?: any;
    isEditing?: boolean;
    isCreatingPost?: boolean;
    isPostsExpanded?: boolean;
    editingPostId?: number | null;
}

interface Emits {
    (e: 'edit', group: Group): void;
    (e: 'createPost', group: Group): void;
    (e: 'togglePosts', group: Group): void;
    (e: 'submitEdit', form: any, group: Group): void;
    (e: 'cancelEdit'): void;
    (e: 'submitCreatePost', form: any): void;
    (e: 'cancelCreatePost'): void;
    (e: 'editPost', post: PostItem): void;
    (e: 'submitEditPost', form: any, post: PostItem): void;
    (e: 'cancelEditPost'): void;
    (e: 'togglePostExtensions', post: PostItem): void;
    (e: 'postUpdated'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

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
} = useListItemActions<Group, PostItem>(emit);

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
</script>

<template>
    <BaseListItem
        :is-creating-post="localIsCreatingPost"
        :is-editing="localIsEditing"
        :is-posts-expanded="localIsPostsExpanded"
        :item="group"
        :subtitle="group.slug"
    >
        <template #actions>
            <ItemActionGroup
                :is-creating-post="localIsCreatingPost"
                :is-editing="localIsEditing"
                :is-posts-expanded="localIsPostsExpanded"
                @edit="handleEdit(group)"
                @create-post="handleCreatePost(group)"
                @toggle-posts="handleTogglePosts(group)"
            >
                <template #prefix>
                    <TooltipButton
                        :tooltip-content="group.is_published ? t('blogger.actions.view_group') : t('blogger.actions.preview_group')"
                        size="icon"
                        variant="ghost"
                        @click="() => router.visit(route('group.landing', group.slug))"
                    >
                        <Badge class="flex h-8 w-8 items-center justify-center p-0" variant="outline">
                            <span class="text-xs">URL</span>
                        </Badge>
                    </TooltipButton>
                </template>
            </ItemActionGroup>
        </template>

        <template #edit-form>
            <div class="mt-4 border-t pt-4">
                <GroupForm :form="editForm" :group="group" is-edit @cancel="handleCancelEdit" @submit="(form) => emit('submitEdit', form, group)" />
            </div>
        </template>

        <template #create-post-form>
            <div class="mt-4 border-t pt-4">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-sm font-medium">{{ t('blogger.actions.add_post_to') }} {{ group.name }}</h3>
                </div>
                <PostForm :form="postForm" @cancel="handleCancelCreatePost" @submit="(form) => emit('submitCreatePost', form)" />
            </div>
        </template>

        <template #posts-list>
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-medium">{{ t('blogger.posts_list_title') }} ({{ group.posts?.length || 0 }})</h3>
            </div>

            <div v-if="group.posts && group.posts.length > 0" class="mt-4 space-y-3">
                <PostListItem
                    v-for="post in group.posts"
                    :key="post.id"
                    :edit-form="postEditForm"
                    :is-editing="localEditingPostId === post.id"
                    :is-extensions-expanded="false"
                    :post="post"
                    @edit="handleEditPost"
                    @updated="emit('postUpdated')"
                    @cancel-edit="handleCancelEditPost"
                    @submit-edit="(form) => emit('submitEditPost', form, post)"
                    @toggle-extensions="emit('togglePostExtensions', post)"
                />
            </div>
            <div v-else class="py-4 text-center text-sm text-muted-foreground">
                {{ t('blogger.no_posts') }}
            </div>
        </template>
    </BaseListItem>
</template>
