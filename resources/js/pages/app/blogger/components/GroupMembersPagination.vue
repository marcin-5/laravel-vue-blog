<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import type { Pagination } from '@/types';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    pagination?: Pagination | null;
}>();

const emit = defineEmits<{
    visitPage: [url: string];
}>();

const { t } = useI18n();
const paginationLinks = computed(() => props.pagination?.links ?? []);

function translatePaginationLabel(label: string): string {
    const cleanedLabel = label
        .replace(/<[^>]*>/g, '')
        .replace(/[«»]|&[lr]aquo;/g, '')
        .trim();
    const normalizedLabel = cleanedLabel.toLowerCase();

    if (normalizedLabel.includes('previous')) {
        return t('pagination.previous');
    }

    if (normalizedLabel.includes('next')) {
        return t('pagination.next');
    }

    return cleanedLabel;
}

function handleClick(url: string | null): void {
    if (url) {
        emit('visitPage', url);
    }
}
</script>

<template>
    <div v-if="paginationLinks.length > 0" class="flex flex-wrap items-center gap-2">
        <Button
            v-for="(link, index) in paginationLinks"
            :key="`${link.url ?? 'disabled'}-${link.label}-${index}`"
            :disabled="!link.url"
            :variant="link.active ? 'default' : 'outline'"
            @click="handleClick(link.url)"
        >
            {{ translatePaginationLabel(link.label) }}
        </Button>
    </div>
</template>
