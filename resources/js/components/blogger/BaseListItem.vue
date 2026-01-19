<script lang="ts" setup>
import { Badge } from '@/components/ui/badge';
import type { ManageableItem } from '@/types/blog.types';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

interface Props {
    item: ManageableItem;
    isEditing: boolean;
    isCreatingPost: boolean;
    isPostsExpanded: boolean;
}

defineProps<Props>();
</script>

<template>
    <div class="rounded-md border border-sidebar-border/70 p-4 dark:border-sidebar-border">
        <div class="flex items-start justify-between gap-4">
            <div class="flex-1">
                <slot name="header">
                    <div class="text-base font-medium">{{ item.name }}</div>
                    <div class="text-xs text-muted-foreground">/{{ item.slug }}</div>
                </slot>
            </div>

            <div class="flex items-center gap-2">
                <Badge :variant="item.is_published ? 'success' : 'accent'">
                    {{ item.is_published ? t('blogger.badges.published') : t('blogger.badges.draft') }}
                </Badge>

                <slot name="actions" />
            </div>
        </div>

        <slot v-if="isEditing" name="edit-form" />

        <slot v-if="isCreatingPost" name="create-post-form" />

        <div v-if="isPostsExpanded" class="mt-4 border-t pt-4">
            <slot name="posts-list" />
        </div>
    </div>
</template>
