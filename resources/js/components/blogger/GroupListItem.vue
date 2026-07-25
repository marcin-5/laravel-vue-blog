<script lang="ts" setup>
import BloggerListItem from '@/components/blogger/BloggerListItem.vue';
import GroupForm from '@/components/blogger/GroupForm.vue';
import ItemActionGroup from '@/components/blogger/ItemActionGroup.vue';
import PostForm from '@/components/blogger/PostForm.vue';
import { Badge } from '@/components/ui/badge';
import { TooltipButton } from '@/components/ui/tooltip';
import { useGroupItemState } from '@/composables/useGroupItemState';
import type { AppPageProps } from '@/types';
import type { AdminGroup as Group, AdminPostItem as PostItem, BloggerItemContext } from '@/types/blog.types';
import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const page = usePage<AppPageProps>();
const props = defineProps<{ item: Group }>();

const canEdit = computed(() => page.props.auth.user?.id === props.item.user_id);
const state = useGroupItemState(props.item);
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
const context = computed<BloggerItemContext<Group, PostItem>>(() => ({
    item: props.item,
    subtitle: props.item.slug,
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
    router.reload({ only: ['groups'] });
}
</script>

<template>
    <BloggerListItem :context="context" @post-updated="handleReload">
        <template #actions="{ handleEdit, handleCreatePost, handleTogglePosts, isCreatingPost, isEditing, isPostsExpanded }">
            <ItemActionGroup
                :can-edit="canEdit"
                :is-creating-post="isCreatingPost"
                :is-editing="isEditing"
                :is-posts-expanded="isPostsExpanded"
                @edit="handleEdit"
                @create-post="handleCreatePost"
                @toggle-posts="handleTogglePosts"
            >
                <template #prefix>
                    <TooltipButton
                        :tooltip-content="item.is_published ? t('blogger.actions.view_group') : t('blogger.actions.preview_group')"
                        size="icon"
                        variant="ghost"
                        @click="() => router.visit(route('group.landing', item.slug))"
                    >
                        <Badge class="flex h-8 w-8 items-center justify-center p-0" variant="outline">
                            <span class="text-xs">URL</span>
                        </Badge>
                    </TooltipButton>
                </template>
            </ItemActionGroup>
        </template>

        <template #edit-form="{ handleCancelEdit }">
            <div class="mt-4 border-t pt-4">
                <GroupForm :form="editForm" :group="item" is-edit @cancel="handleCancelEdit" @submit="submitEdit" />
            </div>
        </template>

        <template #create-post-form="">
            <div class="mt-4 border-t pt-4">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-sm font-medium">{{ t('blogger.actions.add_post_to') }} {{ item.name }}</h3>
                </div>
                <PostForm :form="postForm" :group-id="item.id" @cancel="cancelCreatePost" @submit="submitCreatePost" />
            </div>
        </template>
    </BloggerListItem>
</template>
