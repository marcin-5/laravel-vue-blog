<script lang="ts" setup>
import GroupForm from '@/components/blogger/GroupForm.vue';
import PostForm from '@/components/blogger/PostForm.vue';
import PostListItem from '@/components/blogger/PostListItem.vue';
import { Badge } from '@/components/ui/badge';
import { TooltipButton } from '@/components/ui/tooltip';
import type { AdminGroup as Group, AdminPostItem as PostItem } from '@/types/blog.types';
import { router } from '@inertiajs/vue3';
import { ChevronDown, ChevronUp, Pencil, Plus } from 'lucide-vue-next';

interface Props {
    group: Group;
    isEditing: boolean;
    isCreatingPost: boolean;
    isPostsExpanded: boolean;
    editingPostId: number | null;
    expandedPostExtensionsId: number | null;
    postEditForm?: any;
    postForm?: any;
    editForm?: any;
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

function handleEdit() {
    emit('edit', props.group);
}

function handleCreatePost() {
    emit('createPost', props.group);
}

function handleTogglePosts() {
    emit('togglePosts', props.group);
}
</script>

<template>
    <div class="rounded-md border border-sidebar-border/70 p-4 dark:border-sidebar-border">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="text-base font-medium">{{ group.name }}</div>
                <div class="mt-1 text-sm text-muted-foreground">{{ group.slug }}</div>
            </div>

            <div class="flex items-center gap-1">
                <Badge :variant="group.is_published ? 'success' : 'accent'">
                    {{ group.is_published ? $t('blogger.badges.published') : $t('blogger.badges.draft') }}
                </Badge>
                <div class="flex items-center gap-1">
                    <TooltipButton
                        :tooltip-content="group.is_published ? $t('blogger.actions.view_group') : $t('blogger.actions.preview_group')"
                        size="icon"
                        variant="ghost"
                        @click="() => router.visit(route('group.landing', group.slug))"
                    >
                        <Badge class="flex h-8 w-8 items-center justify-center p-0" variant="outline">
                            <span class="text-xs">URL</span>
                        </Badge>
                    </TooltipButton>

                    <TooltipButton :tooltip-content="$t('blogger.actions.edit')" size="icon" variant="ghost" @click="handleEdit">
                        <Pencil class="h-4 w-4" />
                    </TooltipButton>

                    <TooltipButton :tooltip-content="$t('blogger.groups.add_post')" size="icon" variant="ghost" @click="handleCreatePost">
                        <Plus class="h-4 w-4" />
                    </TooltipButton>

                    <TooltipButton
                        :tooltip-content="isPostsExpanded ? $t('blogger.actions.hide_posts') : $t('blogger.actions.show_posts')"
                        size="icon"
                        variant="ghost"
                        @click="handleTogglePosts"
                    >
                        <ChevronUp v-if="isPostsExpanded" class="h-4 w-4" />
                        <ChevronDown v-else class="h-4 w-4" />
                    </TooltipButton>
                </div>
            </div>
        </div>

        <!-- Edit Group Form -->
        <div v-if="isEditing" class="mt-4 border-t pt-4">
            <GroupForm :form="editForm" :group="group" is-edit @cancel="emit('cancelEdit')" @submit="(form) => emit('submitEdit', form, group)" />
        </div>

        <!-- Create Post Form -->
        <div v-if="isCreatingPost" class="mt-4 border-t pt-4">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-sm font-medium">{{ $t('blogger.actions.add_post_to') }} {{ group.name }}</h3>
            </div>
            <PostForm :form="postForm" @cancel="emit('cancelCreatePost')" @submit="(form) => emit('submitCreatePost', form)" />
        </div>

        <!-- Posts List -->
        <div v-if="isPostsExpanded" class="mt-4 space-y-3 border-t pt-4">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-medium">{{ $t('blogger.posts_list_title') }} ({{ group.posts?.length || 0 }})</h3>
            </div>

            <div v-if="group.posts && group.posts.length > 0" class="space-y-3">
                <PostListItem
                    v-for="post in group.posts"
                    :key="post.id"
                    :edit-form="postEditForm"
                    :is-editing="editingPostId === post.id"
                    :is-extensions-expanded="expandedPostExtensionsId === post.id"
                    :post="post"
                    @edit="emit('editPost', post)"
                    @updated="emit('postUpdated')"
                    @cancel-edit="emit('cancelEditPost')"
                    @submit-edit="(form) => emit('submitEditPost', form, post)"
                    @toggle-extensions="emit('togglePostExtensions', post)"
                />
            </div>
            <div v-else class="py-4 text-center text-sm text-muted-foreground">
                {{ $t('blogger.no_posts') }}
            </div>
        </div>
    </div>
</template>
