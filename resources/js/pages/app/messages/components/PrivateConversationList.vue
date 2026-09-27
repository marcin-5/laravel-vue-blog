<script setup lang="ts">
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import type { PrivateConversation } from '@/types/private-messaging.types';
import { formatDate } from '@/utils/dateUtils';
import { useI18n } from 'vue-i18n';

defineProps<{ conversations: PrivateConversation[]; selectedId?: number | null }>();
const emit = defineEmits<{ select: [conversation: PrivateConversation] }>();
const { t } = useI18n();
</script>

<template>
    <div class="divide-y rounded-lg border">
        <Tooltip v-for="conversation in conversations" :key="conversation.id">
            <TooltipTrigger as-child>
                <button
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
            </TooltipTrigger>
            <TooltipContent v-if="conversation.source">
                <a :href="conversation.source.url" class="underline" target="_blank" rel="noreferrer">
                    {{
                        t(
                            `messages.private_messaging.panel.source_${conversation.source.type}`,
                            { label: conversation.source.label },
                        )
                    }}
                </a>
            </TooltipContent>
        </Tooltip>
    </div>
</template>
