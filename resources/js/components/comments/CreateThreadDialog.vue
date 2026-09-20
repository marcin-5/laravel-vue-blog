<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { Thread } from '@/types/blog.types';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const props = withDefaults(
    defineProps<{
        open: boolean;
        isGroup?: boolean;
        processing?: boolean;
        error?: string;
    }>(),
    {
        isGroup: false,
        processing: false,
        error: '',
    },
);

const emit = defineEmits<{
    (event: 'update:open', value: boolean): void;
    (event: 'submit', payload: { title: string; visibility: Thread['visibility']; content: string }): void;
}>();

const { t } = useI18n();
const title = ref('');
const content = ref('');
const visibility = ref<Thread['visibility']>('public');
const open = computed({
    get: () => props.open,
    set: (value: boolean) => emit('update:open', value),
});

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            title.value = '';
            content.value = '';
            visibility.value = 'public';
        }
    },
);

function submit(): void {
    if (!title.value.trim() || !content.value.trim()) {
        return;
    }

    emit('submit', {
        title: title.value.trim(),
        visibility: props.isGroup ? 'registered' : visibility.value,
        content: content.value.trim(),
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent>
            <form class="space-y-6" @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>{{ t('comments.create_thread.title', 'Start a discussion') }}</DialogTitle>
                    <DialogDescription>
                        {{ t('comments.create_thread.description', 'Choose a topic and write the opening comment.') }}
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4">
                    <div class="grid gap-2">
                        <Label for="thread-title">{{ t('comments.create_thread.subject', 'Topic') }}</Label>
                        <Input id="thread-title" v-model="title" required :placeholder="t('comments.create_thread.subject_placeholder', 'Discussion topic')" />
                    </div>

                    <div v-if="!isGroup" class="grid gap-2">
                        <Label for="thread-visibility">{{ t('comments.create_thread.visibility', 'Visibility') }}</Label>
                        <select
                            id="thread-visibility"
                            v-model="visibility"
                            class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:ring-[3px] focus-visible:ring-ring"
                        >
                            <option value="public">{{ t('comments.visibility.public', 'Public') }}</option>
                            <option value="registered">{{ t('comments.visibility.registered', 'Registered users') }}</option>
                        </select>
                    </div>

                    <div class="grid gap-2">
                        <Label for="thread-content">{{ t('comments.create_thread.content', 'Opening comment') }}</Label>
                        <Textarea
                            id="thread-content"
                            v-model="content"
                            required
                            :placeholder="t('comments.create_thread.content_placeholder', 'Write the first comment...')"
                            rows="6"
                        />
                    </div>
                    <p v-if="error" class="text-sm text-destructive">{{ error }}</p>
                </div>

                <DialogFooter>
                    <Button type="button" variant="secondary" @click="open = false">
                        {{ t('common.cancel', 'Cancel') }}
                    </Button>
                    <Button :disabled="processing || !title.trim() || !content.trim()" type="submit">
                        {{ processing ? t('comments.actions.creating', 'Creating...') : t('comments.actions.create', 'Start discussion') }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
