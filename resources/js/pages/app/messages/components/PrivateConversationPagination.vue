<script setup lang="ts">
import { Button } from '@/components/ui/button';
import type { PrivateConversationPagination } from '@/types/private-messaging.types';
import { useI18n } from 'vue-i18n';

const props = defineProps<{ pagination: PrivateConversationPagination }>();
const emit = defineEmits<{ visitPage: [url: string] }>();
const { t } = useI18n();

function label(value: string): string {
    const text = value
        .replace(/<[^>]*>/g, '')
        .replace(/[«»]|&[lr]aquo;/g, '')
        .trim()
        .toLowerCase();
    if (text.includes('previous')) return t('pagination.previous', 'Previous');
    if (text.includes('next')) return t('pagination.next', 'Next');
    return text;
}
</script>

<template>
    <div v-if="props.pagination.links.length > 0" class="flex flex-wrap gap-2">
        <Button
            v-for="(link, index) in props.pagination.links"
            :key="`${link.url ?? 'disabled'}-${index}`"
            :disabled="!link.url"
            :variant="link.active ? 'default' : 'outline'"
            @click="link.url && emit('visitPage', link.url)"
        >
            {{ label(link.label) }}
        </Button>
    </div>
</template>
