<script setup lang="ts">
import type { PrivateConversation } from '@/types/private-messaging.types';
import { formatDate } from '@/utils/dateUtils';

defineProps<{ conversations: PrivateConversation[]; selectedId?: number | null }>();
const emit = defineEmits<{ select: [conversation: PrivateConversation] }>();
</script>

<template>
    <div class="divide-y rounded-lg border">
        <button
            v-for="conversation in conversations"
            :key="conversation.id"
            class="block w-full px-4 py-3 text-left transition-colors hover:bg-muted/50"
            :class="conversation.id === selectedId ? 'bg-muted' : ''"
            type="button"
            @click="emit('select', conversation)"
        >
            <span class="flex items-start justify-between gap-3">
                <span class="font-medium">{{ conversation.subject }}</span>
            </span>
            <span class="mt-1 flex items-start justify-between gap-3 text-xs text-muted-foreground">
                <span>{{ conversation.initiator.name }} · {{ conversation.messages_count ?? 0 }}</span>
                <span class="shrink-0">{{ formatDate(conversation.updated_at) }}</span>
            </span>
        </button>
    </div>
</template>
