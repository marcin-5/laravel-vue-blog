<script lang="ts" setup>
import CommentReplyForm from '@/components/comments/CommentReplyForm.vue';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import type { Comment } from '@/types/blog.types';
import { Check, Pencil, Reply, Trash2, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const props = withDefaults(
    defineProps<{
        comment: Comment;
        commentsMaxDepth?: number;
        isAuthenticated: boolean;
        canReply: boolean;
        currentUserId?: number | null;
    }>(),
    {
        commentsMaxDepth: 5,
    },
);

const emit = defineEmits<{
    (event: 'reply', payload: { parentId: number; content: string }): void;
    (event: 'edit', payload: { commentId: number; content: string }): void;
    (event: 'delete', commentId: number): void;
}>();

const { t } = useI18n();
const isReplying = ref(false);
const isEditing = ref(false);
const editedContent = ref(props.comment.content);
const INDENTATION_STEP_REM = 0.75;
const MAX_INDENTATION_LEVEL = 6;
const hasChildren = computed(() => Boolean(props.comment.children?.length));
const isCommentOwner = computed(() => props.currentUserId === props.comment.user_id);
const canReplyAtDepth = computed(() => props.commentsMaxDepth === 0 || props.comment.depth < props.commentsMaxDepth);
const indentation = computed(() => `${Math.min(Math.max(props.comment.depth - 1, 0), MAX_INDENTATION_LEVEL) * INDENTATION_STEP_REM}rem`);

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

function startEditing(): void {
    editedContent.value = props.comment.content;
    isEditing.value = true;
}

function submitEdit(): void {
    const content = editedContent.value.trim();
    if (!content) {
        return;
    }

    emit('edit', { commentId: props.comment.id, content });
    isEditing.value = false;
}

function deleteComment(): void {
    if (!confirm(t('comments.confirm.delete_comment', 'Delete this comment and all dependent replies?'))) {
        return;
    }

    emit('delete', props.comment.id);
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
            <Textarea v-if="isEditing" v-model="editedContent" class="text-sm" rows="4" />
            <p v-else class="text-sm wrap-break-word whitespace-pre-wrap text-foreground">{{ comment.content }}</p>
            <div v-if="currentUserId === comment.user_id" class="mt-3 flex flex-wrap gap-2">
                <template v-if="isEditing">
                    <Button class="h-8 px-2 text-xs" size="sm" type="button" @click="submitEdit">
                        <Check class="mr-1 h-3.5 w-3.5" />
                        {{ t('comments.actions.save', 'Save') }}
                    </Button>
                    <Button class="h-8 px-2 text-xs" size="sm" type="button" variant="ghost" @click="isEditing = false">
                        <X class="mr-1 h-3.5 w-3.5" />
                        {{ t('common.cancel', 'Cancel') }}
                    </Button>
                </template>
                <template v-else>
                    <Button class="h-8 px-2 text-xs" size="sm" type="button" variant="ghost" @click="startEditing">
                        <Pencil class="mr-1 h-3.5 w-3.5" />
                        {{ t('comments.actions.edit', 'Edit') }}
                    </Button>
                    <Button
                        class="h-8 px-2 text-xs text-destructive hover:text-destructive-hover"
                        size="sm"
                        type="button"
                        variant="ghost"
                        @click="deleteComment"
                    >
                        <Trash2 class="mr-1 h-3.5 w-3.5" />
                        {{ t('comments.actions.delete', 'Delete') }}
                    </Button>
                </template>
            </div>
            <Button
                v-if="canReply && !isCommentOwner && canReplyAtDepth"
                class="mt-3 h-8 px-2 text-xs"
                size="sm"
                type="button"
                variant="ghost"
                @click="isReplying = !isReplying"
            >
                <Reply class="mr-1 h-3.5 w-3.5" />
                {{ t('comments.actions.reply', 'Reply') }}
            </Button>
            <p v-else-if="canReply && !isCommentOwner && !canReplyAtDepth" class="mt-3 text-xs text-muted-foreground">
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
                :current-user-id="currentUserId"
                :is-authenticated="isAuthenticated"
                @delete="emit('delete', $event)"
                @edit="emit('edit', $event)"
                @reply="emit('reply', $event)"
            />
        </div>
    </article>
</template>
