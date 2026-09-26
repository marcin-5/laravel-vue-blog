<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import type { PrivateConversation, PrivateMessage } from '@/types/private-messaging.types';
import { formatDateTime } from '@/utils/dateUtils';
import { useForm } from '@inertiajs/vue3';
import { shallowRef } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{ conversation: PrivateConversation; userId: number }>();
const { t } = useI18n();
const replyForm = useForm({ content: '' });
const notificationForm = useForm({ email_notifications: props.conversation.email_notifications ?? true });
const editingId = shallowRef<number | null>(null);
const editForm = useForm({ content: '' });

function startEdit(message: PrivateMessage): void {
    editingId.value = message.id;
    editForm.content = message.content;
    editForm.clearErrors();
}

function saveEdit(message: PrivateMessage): void {
    editForm.patch(route('private-messages.update', message.id), { preserveScroll: true, onSuccess: () => (editingId.value = null) });
}

function deleteMessage(message: PrivateMessage): void {
    if (!window.confirm(t('messages.private_messaging.panel.confirm_delete', 'Delete this message?'))) return;
    useForm({}).delete(route('private-messages.destroy', message.id), { preserveScroll: true });
}

function reply(): void {
    if (!replyForm.content.trim()) return;
    replyForm
        .transform((data) => ({ content: data.content.trim() }))
        .post(route('private-conversations.messages.store', props.conversation.id), {
            preserveScroll: true,
            onSuccess: () => replyForm.reset(),
        });
}

function updateNotifications(value: boolean): void {
    notificationForm.email_notifications = value;
    notificationForm.patch(route('private-conversations.notifications.update', props.conversation.id), { preserveScroll: true });
}
</script>

<template>
    <section class="space-y-5 rounded-lg border p-4">
        <header class="flex flex-wrap items-center justify-between gap-3 border-b pb-4">
            <div>
                <h2 class="text-xl font-semibold">{{ props.conversation.subject }}</h2>
                <p class="text-sm text-muted-foreground">{{ props.conversation.initiator.name }} · {{ props.conversation.owner.name }}</p>
            </div>
            <label class="flex items-center gap-2 text-sm text-muted-foreground">
                <Switch :model-value="notificationForm.email_notifications" @update:model-value="updateNotifications(Boolean($event))" />
                {{ t('messages.private_messaging.panel.notifications', 'Email notifications for replies') }}
            </label>
        </header>

        <div class="space-y-3">
            <article v-for="message in props.conversation.messages ?? []" :key="message.id" class="rounded-md bg-muted/40 p-3">
                <div class="mb-2 flex items-center justify-between gap-3 text-xs text-muted-foreground">
                    <span>{{ message.author?.name }}</span>
                    <span>{{ formatDateTime(message.created_at) }}</span>
                </div>
                <template v-if="editingId === message.id">
                    <Textarea v-model="editForm.content" rows="4" />
                    <div class="mt-2 flex gap-2">
                        <Button :disabled="editForm.processing || !editForm.content.trim()" size="sm" @click="saveEdit(message)">{{
                            t('messages.private_messaging.actions.save', 'Save')
                        }}</Button>
                        <Button size="sm" variant="ghost" @click="editingId = null">{{
                            t('messages.private_messaging.actions.close', 'Close')
                        }}</Button>
                    </div>
                </template>
                <p v-else class="text-sm whitespace-pre-wrap">{{ message.content }}</p>
                <div v-if="message.can_edit && message.user_id === props.userId && editingId !== message.id" class="mt-2 flex gap-2">
                    <Button size="sm" variant="ghost" @click="startEdit(message)">{{ t('messages.private_messaging.actions.edit', 'Edit') }}</Button>
                    <Button size="sm" variant="ghost" @click="deleteMessage(message)">{{
                        t('messages.private_messaging.actions.delete', 'Delete')
                    }}</Button>
                </div>
            </article>
        </div>

        <form class="space-y-2 border-t pt-4" @submit.prevent="reply">
            <Textarea
                v-model="replyForm.content"
                :placeholder="t('messages.private_messaging.panel.reply_placeholder', 'Write a reply...')"
                rows="4"
            />
            <Button :disabled="replyForm.processing || !replyForm.content.trim()" type="submit">
                {{
                    replyForm.processing
                        ? t('messages.private_messaging.actions.sending', 'Sending...')
                        : t('messages.private_messaging.actions.reply', 'Reply')
                }}
            </Button>
        </form>
    </section>
</template>
