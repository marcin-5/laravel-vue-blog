<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    open: boolean;
    postId: number;
    storeUrl?: string | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const { t } = useI18n();
const form = useForm({
    post_id: props.postId,
    subject: '',
    content: '',
    email_notifications: true,
});
const open = computed({
    get: () => props.open,
    set: (value: boolean) => emit('update:open', value),
});

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) form.clearErrors();
    },
);

function submit(): void {
    if (!props.storeUrl || !form.subject.trim() || !form.content.trim()) return;

    form.transform((data) => ({
        ...data,
        subject: data.subject.trim(),
        content: data.content.trim(),
    })).post(props.storeUrl, {
        onSuccess: () => {
            form.reset();
            form.email_notifications = true;
            open.value = false;
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent>
            <form class="space-y-6" novalidate @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>{{ t('messages.private_messaging.dialog.title', 'Send private message') }}</DialogTitle>
                    <DialogDescription>
                        {{ t('messages.private_messaging.dialog.description', 'Only you and the post owner can see this conversation.') }}
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4">
                    <div class="grid gap-2">
                        <Label for="private-message-subject">{{ t('messages.private_messaging.fields.subject', 'Subject') }}</Label>
                        <Input
                            id="private-message-subject"
                            v-model="form.subject"
                            required
                            :aria-invalid="Boolean(form.errors.subject)"
                            :placeholder="t('messages.private_messaging.fields.subject_placeholder', 'Message subject')"
                            @focus="form.clearErrors('subject')"
                        />
                        <p v-if="form.errors.subject" class="text-sm text-destructive">{{ form.errors.subject }}</p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="private-message-content">{{ t('messages.private_messaging.fields.content', 'Message') }}</Label>
                        <Textarea
                            id="private-message-content"
                            v-model="form.content"
                            required
                            :aria-invalid="Boolean(form.errors.content)"
                            :placeholder="t('messages.private_messaging.fields.content_placeholder', 'Write your message...')"
                            rows="7"
                            @focus="form.clearErrors('content')"
                        />
                        <p v-if="form.errors.content" class="text-sm text-destructive">{{ form.errors.content }}</p>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-muted-foreground">
                        <Checkbox v-model:checked="form.email_notifications" />
                        <span>{{ t('messages.private_messaging.fields.email_notifications', 'Notify me by email about replies') }}</span>
                    </label>
                </div>

                <DialogFooter>
                    <Button type="button" variant="secondary" @click="open = false">{{ t('common.cancel', 'Cancel') }}</Button>
                    <Button :disabled="form.processing || !form.subject.trim() || !form.content.trim()" type="submit">
                        {{
                            form.processing
                                ? t('messages.private_messaging.actions.sending', 'Sending...')
                                : t('messages.private_messaging.actions.send', 'Send message')
                        }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
