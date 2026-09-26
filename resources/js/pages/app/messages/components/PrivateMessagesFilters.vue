<script setup lang="ts">
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { PrivateConversationFilters, PrivateConversationSortDirection, PrivateConversationSortField } from '@/types/private-messaging.types';
import type { AcceptableValue } from 'reka-ui';
import { useI18n } from 'vue-i18n';

defineProps<{ filters: PrivateConversationFilters }>();
const emit = defineEmits<{
    sortByChange: [value: AcceptableValue | undefined];
    sortDirChange: [value: AcceptableValue | undefined];
    perPageChange: [value: AcceptableValue | undefined];
}>();
const { t } = useI18n();
</script>

<template>
    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
        <div class="grid gap-1">
            <label class="text-sm text-muted-foreground">{{ t('messages.private_messaging.panel.sort_by', 'Sort by') }}</label>
            <Select :model-value="filters.sort_by" @update:model-value="emit('sortByChange', $event)">
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="subject">{{ t('messages.private_messaging.panel.sort_subject', 'Subject') }}</SelectItem>
                    <SelectItem value="created_at">{{ t('messages.private_messaging.panel.sort_created', 'Created date') }}</SelectItem>
                    <SelectItem value="updated_at">{{ t('messages.private_messaging.panel.sort_updated', 'Updated date') }}</SelectItem>
                </SelectContent>
            </Select>
        </div>
        <div class="grid gap-1">
            <label class="text-sm text-muted-foreground">{{ t('messages.private_messaging.panel.direction', 'Direction') }}</label>
            <Select :model-value="filters.sort_dir" @update:model-value="emit('sortDirChange', $event)">
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="asc">{{ t('messages.private_messaging.panel.ascending', 'Ascending') }}</SelectItem>
                    <SelectItem value="desc">{{ t('messages.private_messaging.panel.descending', 'Descending') }}</SelectItem>
                </SelectContent>
            </Select>
        </div>
        <div class="grid gap-1">
            <label class="text-sm text-muted-foreground">{{ t('list.per_page', 'Per page') }}</label>
            <Select :model-value="String(filters.per_page)" @update:model-value="emit('perPageChange', $event)">
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="10">10</SelectItem>
                    <SelectItem value="20">20</SelectItem>
                    <SelectItem value="50">50</SelectItem>
                </SelectContent>
            </Select>
        </div>
    </div>
</template>
