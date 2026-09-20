<script lang="ts" setup>
import CommentReplyForm from '@/components/comments/CommentReplyForm.vue';
import { Button } from '@/components/ui/button';
import type { Comment } from '@/types/blog.types';
import { Reply } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const props = withDefaults(
    defineProps<{
        comment: Comment;
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
const isReplying = ref(false);
const INDENTATION_STEP_REM = 0.75;
const MAX_INDENTATION_LEVEL = 6;
const hasChildren = computed(() => Boolean(props.comment.children?.length));
const canReplyAtDepth = computed(() => props.commentsMaxDepth === 0 || props.comment.depth < props.commentsMaxDepth);
const indentation = computed(
    () => `${Math.min(Math.max(props.comment.depth - 1, 0), MAX_INDENTATION_LEVEL) * INDENTATION_STEP_REM}rem`,
);

function formatDate(value?: string): string {
    if (!value) {
        return '';
    }

    return new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
}

function submitReply(payload: { content: string }): void {
    emit('reply', { parentId: props.comment.id, content: payload.content });
    isReplying.value = false;
}
</script>

<template>
    <article :style="{ paddingInlineStart: indentation }" class="border-s border-border ps-4">
        <div class="rounded-md bg-muted/30 p-3">
            <div class="mb-2 flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1 text-sm">
                <strong class="text-foreground">{{ comment.user?.name ?? t('comments.unknown_author', 'Community member') }}</strong>
                <time v-if="comment.created_at" :datetime="comment.created_at" class="text-xs text-muted-foreground">
                    {{ formatDate(comment.created_at) }}
                </time>
            </div>
            <p class="text-sm wrap-break-word whitespace-pre-wrap text-foreground">{{ comment.content }}</p>
            <Button
                v-if="canReply && canReplyAtDepth"
                class="mt-3 h-8 px-2 text-xs"
                size="sm"
                type="button"
                variant="ghost"
                @click="isReplying = !isReplying"
            >
                <Reply class="mr-1 h-3.5 w-3.5" />
                {{ t('comments.actions.reply', 'Reply') }}
            </Button>
            <p v-else-if="canReply && !canReplyAtDepth" class="mt-3 text-xs text-muted-foreground">
                {{ t('comments.max_depth', 'The maximum reply depth has been reached.') }}
            </p>
            <CommentReplyForm
                v-if="isReplying"
                :is-authenticated="true"
                :show-cancel="true"
                class="mt-3"
                @cancel="isReplying = false"
                @submit="submitReply"
            />
        </div>

        <div v-if="hasChildren" class="mt-3 space-y-3">
            <CommentItem
                v-for="child in comment.children"
                :key="child.id"
                :can-reply="canReply"
                :comment="child"
                :comments-max-depth="commentsMaxDepth"
                :is-authenticated="isAuthenticated"
                @reply="emit('reply', $event)"
            />
        </div>
    </article>
</template>
