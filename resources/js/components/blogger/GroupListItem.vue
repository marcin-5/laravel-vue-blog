<script lang="ts" setup>
import BloggerListItem from '@/components/blogger/BloggerListItem.vue';
import GroupForm from '@/components/blogger/GroupForm.vue';
import ItemActionGroup from '@/components/blogger/ItemActionGroup.vue';
import PostForm from '@/components/blogger/PostForm.vue';
import { Badge } from '@/components/ui/badge';
import { TooltipButton } from '@/components/ui/tooltip';
import type { AdminGroup as Group, AdminPostItem as PostItem, ListItemEmits, ListItemProps } from '@/types/blog.types';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps<ListItemProps<Group>>();
const emit = defineEmits<ListItemEmits<Group, PostItem>>();
</script>

<template>
    <BloggerListItem
        :subtitle="item.slug"
        v-bind="props"
        @edit="(i) => emit('edit', i)"
        @apply-edit-extension="(form, ext) => emit('applyEditExtension', form, ext)"
        @cancel-create-extension="emit('cancelCreateExtension')"
        @cancel-edit="emit('cancelEdit')"
        @cancel-edit-extension="emit('cancelEditExtension')"
        @cancel-edit-post="emit('cancelEditPost')"
        @cancel-create-post="emit('cancelCreatePost')"
        @create-extension="(p) => emit('createExtension', p)"
        @create-post="(i) => emit('createPost', i)"
        @edit-extension="(ext) => emit('editExtension', ext)"
        @edit-post="(p) => emit('editPost', p)"
        @post-updated="emit('postUpdated')"
        @submit-create-extension="(form, p) => emit('submitCreateExtension', form, p)"
        @submit-create-post="(form) => emit('submitCreatePost', form)"
        @submit-edit="(form, i) => emit('submitEdit', form, i)"
        @submit-edit-extension="(form, ext) => emit('submitEditExtension', form, ext)"
        @submit-edit-post="(form, p) => emit('submitEditPost', form, p)"
        @toggle-extensions="(p) => emit('toggleExtensions', p)"
        @toggle-posts="(i) => emit('togglePosts', i)"
    >
        <template #actions="{ handleEdit, handleCreatePost, handleTogglePosts, isCreatingPost, isEditing, isPostsExpanded }">
            <ItemActionGroup
                :is-creating-post="isCreatingPost"
                :is-editing="isEditing"
                :is-posts-expanded="isPostsExpanded"
                @edit="handleEdit(item)"
                @create-post="handleCreatePost(item)"
                @toggle-posts="handleTogglePosts(item)"
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
                <GroupForm :form="editForm" :group="item" is-edit @cancel="handleCancelEdit" @submit="(form) => emit('submitEdit', form, item)" />
            </div>
        </template>

        <template #create-post-form="{ handleCancelCreatePost }">
            <div class="mt-4 border-t pt-4">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-sm font-medium">{{ t('blogger.actions.add_post_to') }} {{ item.name }}</h3>
                </div>
                <PostForm :form="postForm" @cancel="handleCancelCreatePost" @submit="(form) => emit('submitCreatePost', form)" />
            </div>
        </template>
    </BloggerListItem>
</template>
