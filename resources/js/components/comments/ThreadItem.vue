<script setup lang="ts">
import CommentReplyForm from '@/components/comments/CommentReplyForm.vue';
import CommentTree from '@/components/comments/CommentTree.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Skeleton } from '@/components/ui/skeleton';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import type { Comment, Thread } from '@/types/blog.types';
import { ChevronDown, Lock, LockOpen } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

withDefaults(
    defineProps<{
        thread: Thread;
        expanded: boolean;
        locked: boolean;
        comments?: Comment[];
        loading?: boolean;
        error?: string;
        isAuthenticated: boolean;
        canReply: boolean;
        commentsMaxDepth?: number;
    }>(),
    {
        comments: () => [],
        loading: false,
        error: '',
        commentsMaxDepth: 5,
    },
);

const emit = defineEmits<{
    (event: 'toggle'): void;
    (event: 'toggle-lock'): void;
    (event: 'reply', payload: { parentId: number; content: string }): void;
    (event: 'thread-reply', payload: { content: string }): void;
}>();

const { t } = useI18n();

function formatDate(value?: string): string {
    if (!value) {
        return '';
    }

    return new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' }).format(new Date(value));
}
</script>

<template>
    <div class="rounded-lg border border-border bg-card">
        <Collapsible :open="expanded">
            <div class="flex items-center gap-2 p-4">
                <CollapsibleTrigger
                    class="flex min-w-0 flex-1 items-center gap-3 text-left font-semibold transition-colors hover:text-primary"
                    type="button"
                    @click="emit('toggle')"
                >
                    <ChevronDown :class="{ 'rotate-180': expanded }" class="h-5 w-5 shrink-0 transition-transform duration-200" />
                    <span class="min-w-0 flex-1 truncate">{{ thread.title }}</span>
                    <span class="shrink-0 text-xs font-normal text-muted-foreground">
                        {{ thread.comments_count ?? 0 }}
                    </span>
                </CollapsibleTrigger>
                <Badge v-if="thread.visibility === 'registered'" class="shrink-0" variant="secondary">
                    {{ t('comments.visibility.registered_short', 'Registered') }}
                </Badge>
                <Badge v-if="thread.is_locked" class="shrink-0" variant="outline">
                    {{ t('comments.locked', 'Closed') }}
                </Badge>
                <TooltipProvider>
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <Button
                                :aria-label="locked ? t('comments.unlock', 'Unlock discussion') : t('comments.lock', 'Keep discussion open')"
                                :variant="locked ? 'toggle' : 'locked'"
                                class="h-8 w-8 shrink-0"
                                size="icon"
                                type="button"
                                @click.stop="emit('toggle-lock')"
                            >
                                <Lock v-if="locked" class="h-4 w-4" />
                                <LockOpen v-else class="h-4 w-4" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                            {{ locked ? t('comments.unlock', 'Unlock discussion') : t('comments.lock', 'Keep discussion open') }}
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>
            </div>

            <CollapsibleContent>
                <div class="space-y-4 border-t border-border p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-muted-foreground">
                        <span>{{ thread.author?.name ?? t('comments.unknown_author', 'Community member') }}</span>
                        <time v-if="thread.created_at" :datetime="thread.created_at">{{ formatDate(thread.created_at) }}</time>
                    </div>

                    <div v-if="loading" class="space-y-3" aria-live="polite">
                        <Skeleton class="h-20 w-full" />
                        <Skeleton class="h-16 w-11/12" />
                    </div>
                    <p v-else-if="error" class="rounded-md bg-destructive/10 p-3 text-sm text-destructive">{{ error }}</p>
                    <CommentTree
                        v-else
                        :can-reply="canReply"
                        :comments="comments"
                        :comments-max-depth="commentsMaxDepth"
                        :is-authenticated="isAuthenticated"
                        @reply="emit('reply', $event)"
                    />

                    <div v-if="thread.is_locked" class="rounded-md border border-dashed border-border p-3 text-sm text-muted-foreground">
                        {{ t('comments.thread_locked', 'This discussion is closed for new replies.') }}
                    </div>
                    <CommentReplyForm
                        v-else
                        :is-authenticated="isAuthenticated"
                        :is-thread-level="true"
                        @submit="emit('thread-reply', $event)"
                    />
                </div>
            </CollapsibleContent>
        </Collapsible>
    </div>
</template>
