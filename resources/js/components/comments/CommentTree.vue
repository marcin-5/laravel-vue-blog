<script setup lang="ts">
import CommentItem from '@/components/comments/CommentItem.vue';
import type { Comment } from '@/types/blog.types';
import { useI18n } from 'vue-i18n';

withDefaults(
    defineProps<{
        comments: Comment[];
        commentsMaxDepth?: number;
        isAuthenticated: boolean;
        canReply: boolean;
    }>(),
    {
        commentsMaxDepth: 5,
    },
);

const emit = defineEmits<{
    (event: 'reply', payload: { parentId: number; content: string }): void;
}>();

const { t } = useI18n();
</script>

<template>
    <div v-if="comments.length" class="space-y-3">
        <CommentItem
            v-for="comment in comments"
            :key="comment.id"
            :can-reply="canReply"
            :comment="comment"
            :comments-max-depth="commentsMaxDepth"
            :is-authenticated="isAuthenticated"
            @reply="emit('reply', $event)"
        />
    </div>
    <p v-else class="py-4 text-center text-sm text-muted-foreground">
        {{ t('comments.empty', 'There are no comments in this discussion yet.') }}
    </p>
</template>
