<script setup lang="ts">
import CreateThreadDialog from '@/components/comments/CreateThreadDialog.vue';
import ThreadItem from '@/components/comments/ThreadItem.vue';
import { Button } from '@/components/ui/button';
import { useToast } from '@/composables/useToast';
import type { AppPageProps } from '@/types';
import type { Comment, Thread } from '@/types/blog.types';
import { Link, useHttp, usePage } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

interface ThreadPayload {
    title: string;
    visibility: Thread['visibility'];
    content: string;
}

interface CommentPayload {
    parent_id: number | null;
    content: string;
}

const props = withDefaults(
    defineProps<{
        postId: number;
        threads?: Thread[];
        allowComments?: boolean;
        commentsMaxDepth?: number;
        isGroup?: boolean;
    }>(),
    {
        threads: () => [],
        allowComments: true,
        commentsMaxDepth: 5,
        isGroup: false,
    },
);

const { t } = useI18n();
const { toast } = useToast();
const page = usePage<AppPageProps>();
const isAuthenticated = computed(() => Boolean(page.props.auth?.user));
const threadList = ref<Thread[]>([...props.threads]);
const comments = ref<Record<number, Comment[]>>({});
const loadingThreadIds = ref<Set<number>>(new Set());
const expandedIds = ref<Set<number>>(new Set());
const lockedIds = ref<Set<number>>(new Set());
const errors = ref<Record<number, string>>({});
const showCreateDialog = ref(false);
const isCreatingThread = ref(false);
const createThreadError = ref('');

const threadHttp = useHttp<ThreadPayload, Thread>({ title: '', visibility: 'public', content: '' });
const commentHttp = useHttp<CommentPayload, Comment>({ parent_id: null, content: '' });

const canReply = computed(() => props.allowComments && isAuthenticated.value);

function updateSet(refSet: typeof expandedIds, update: (set: Set<number>) => void): void {
    const next = new Set(refSet.value);
    update(next);
    refSet.value = next;
}

async function loadComments(threadId: number, force = false): Promise<void> {
    if (loadingThreadIds.value.has(threadId) || (!force && comments.value[threadId])) {
        return;
    }

    updateSet(loadingThreadIds, (set) => set.add(threadId));
    errors.value = { ...errors.value, [threadId]: '' };

    try {
        const response = await fetch(route('threads.comments.index', { thread: threadId }), {
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });

        if (!response.ok) {
            throw new Error(t('comments.errors.load', 'Unable to load this discussion.'));
        }

        comments.value = { ...comments.value, [threadId]: (await response.json()) as Comment[] };
    } catch (error) {
        errors.value = {
            ...errors.value,
            [threadId]: error instanceof Error ? error.message : t('comments.errors.load', 'Unable to load this discussion.'),
        };
    } finally {
        updateSet(loadingThreadIds, (set) => set.delete(threadId));
    }
}

function toggleThread(threadId: number): void {
    if (expandedIds.value.has(threadId)) {
        if (!lockedIds.value.has(threadId)) {
            updateSet(expandedIds, (set) => set.delete(threadId));
        }
        return;
    }

    updateSet(expandedIds, (set) => {
        set.forEach((expandedId) => {
            if (!lockedIds.value.has(expandedId)) {
                set.delete(expandedId);
            }
        });
        set.add(threadId);
    });
    void loadComments(threadId);
}

function toggleLock(threadId: number): void {
    updateSet(lockedIds, (set) => {
        if (set.has(threadId)) {
            set.delete(threadId);
        } else {
            set.add(threadId);
        }
    });
}

async function createThread(payload: ThreadPayload): Promise<void> {
    if (!isAuthenticated.value || !props.allowComments) {
        return;
    }

    isCreatingThread.value = true;
    createThreadError.value = '';
    threadHttp.title = payload.title;
    threadHttp.visibility = props.isGroup ? 'registered' : payload.visibility;
    threadHttp.content = payload.content;

    try {
        const thread = (await threadHttp.post(route('posts.threads.store', { post: props.postId }))) as unknown as Thread;
        threadList.value = [thread, ...threadList.value];
        showCreateDialog.value = false;
        toast({ title: t('comments.success.created', 'Discussion started.'), variant: 'success' });
        toggleThread(thread.id);
        await loadComments(thread.id, true);
    } catch (error) {
        createThreadError.value = error instanceof Error ? error.message : t('comments.errors.create', 'Unable to start the discussion.');
    } finally {
        isCreatingThread.value = false;
    }
}

async function createComment(threadId: number, payload: { parentId?: number; content: string }): Promise<void> {
    if (!isAuthenticated.value || !props.allowComments) {
        return;
    }

    commentHttp.parent_id = payload.parentId ?? null;
    commentHttp.content = payload.content;

    try {
        await commentHttp.post(route('threads.comments.store', { thread: threadId }));
        await loadComments(threadId, true);
        toast({ title: t('comments.success.replied', 'Reply added.'), variant: 'success' });
    } catch (error) {
        toast({
            title: error instanceof Error ? error.message : t('comments.errors.reply', 'Unable to add the reply.'),
            variant: 'destructive',
        });
    }
}
</script>

<template>
    <section class="mt-10 space-y-4" aria-labelledby="post-comments-title">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 id="post-comments-title" class="text-xl font-semibold text-foreground">
                    {{ t('comments.title', 'Discussion') }}
                </h2>
                <p v-if="allowComments" class="text-sm text-muted-foreground">
                    {{ t('comments.subtitle', 'Discuss this post in organized topics.') }}
                </p>
            </div>
            <Button v-if="allowComments && isAuthenticated" type="button" @click="showCreateDialog = true">
                <Plus class="mr-2 h-4 w-4" />
                {{ t('comments.actions.new_thread', 'New topic') }}
            </Button>
            <Link v-else-if="allowComments" :href="route('login')" class="text-sm font-medium text-primary underline-offset-4 hover:underline">
                {{ t('comments.auth_required', 'Sign in to join this discussion.') }}
            </Link>
        </div>

        <div v-if="!allowComments" class="rounded-lg border border-dashed border-border bg-muted/30 p-4 text-sm text-muted-foreground">
            {{ t('comments.closed', 'Comments are closed for this post.') }}
        </div>
        <div v-else-if="threadList.length === 0" class="rounded-lg border border-dashed border-border bg-muted/30 p-6 text-center text-sm text-muted-foreground">
            {{ t('comments.empty_threads', 'There are no discussion topics yet.') }}
        </div>
        <div v-else class="space-y-3">
            <ThreadItem
                v-for="thread in threadList"
                :key="thread.id"
                :can-reply="canReply"
                :comments="comments[thread.id] ?? []"
                :comments-max-depth="commentsMaxDepth"
                :error="errors[thread.id]"
                :expanded="expandedIds.has(thread.id)"
                :is-authenticated="isAuthenticated"
                :loading="loadingThreadIds.has(thread.id)"
                :locked="lockedIds.has(thread.id)"
                :thread="thread"
                @reply="createComment(thread.id, $event)"
                @thread-reply="createComment(thread.id, $event)"
                @toggle="toggleThread(thread.id)"
                @toggle-lock="toggleLock(thread.id)"
            />
        </div>

        <CreateThreadDialog
            v-model:open="showCreateDialog"
            :error="createThreadError"
            :is-group="isGroup"
            :processing="isCreatingThread"
            @submit="createThread"
        />
    </section>
</template>
