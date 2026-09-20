<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Link } from '@inertiajs/vue3';
import { Send, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const props = withDefaults(
    defineProps<{
        isAuthenticated: boolean;
        disabled?: boolean;
        showCancel?: boolean;
        isThreadLevel?: boolean;
    }>(),
    {
        disabled: false,
        showCancel: false,
        isThreadLevel: false,
    },
);

const emit = defineEmits<{
    (event: 'submit', payload: { content: string }): void;
    (event: 'cancel'): void;
}>();

const { t } = useI18n();
const content = ref('');
const placeholder = computed(() =>
    props.isThreadLevel
        ? t('comments.comment.placeholder', 'Write a comment...')
        : t('comments.reply.placeholder', 'Write a reply...'),
);

function submit(): void {
    const value = content.value.trim();
    if (!value) {
        return;
    }

    emit('submit', { content: value });
    content.value = '';
}
</script>

<template>
    <div v-if="!isAuthenticated" class="rounded-md border border-dashed border-border bg-muted/30 p-3 text-sm text-muted-foreground">
        {{ t('comments.auth_required', 'Sign in to join this discussion.') }}
        <Link :href="route('login')" class="font-medium text-primary underline-offset-4 hover:underline">
            {{ t('auth.login.submit', 'Sign in') }}
        </Link>
    </div>
    <form v-else class="space-y-2" @submit.prevent="submit">
        <Textarea
            v-model="content"
            :aria-label="placeholder"
            :disabled="disabled"
            :placeholder="placeholder"
            rows="3"
        />
        <div class="flex justify-end gap-2">
            <Button v-if="showCancel" type="button" variant="ghost" @click="emit('cancel')">
                <X class="mr-2 h-4 w-4" />
                {{ t('common.cancel', 'Cancel') }}
            </Button>
            <Button :disabled="disabled || !content.trim()" size="sm" type="submit">
                <Send class="mr-2 h-4 w-4" />
                {{ t('comments.actions.reply', 'Reply') }}
            </Button>
        </div>
    </form>
</template>
