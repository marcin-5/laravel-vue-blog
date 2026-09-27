<script setup lang="ts">
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type {
    PrivateConversationContext,
    PrivateConversationContextOption,
    PrivateConversationFilters,
    PrivateConversationSortDirection,
    PrivateConversationSortField,
} from '@/types/private-messaging.types';
import type { AcceptableValue } from 'reka-ui';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    filters: PrivateConversationFilters;
    contextOptions: PrivateConversationContextOption[];
}>();
const emit = defineEmits<{
    contextChange: [value: AcceptableValue | undefined];
    sortByChange: [value: AcceptableValue | undefined];
    sortDirChange: [value: AcceptableValue | undefined];
    perPageChange: [value: AcceptableValue | undefined];
}>();
const { t } = useI18n();

const contextLabel = (context: PrivateConversationContext): string =>
    context === 'blog' ? t('messages.private_messaging.panel.blog', 'Blog') : t('messages.private_messaging.panel.group', 'Group');

const allContextsLabel = (context: PrivateConversationContext): string =>
    context === 'blog'
        ? t('messages.private_messaging.panel.all_blogs', 'All blogs')
        : t('messages.private_messaging.panel.all_groups', 'All groups');
</script>

<template>
    <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
        <div class="grid gap-1">
            <label class="text-sm text-muted-foreground">{{ contextLabel(props.filters.context) }}</label>
            <Select
                :model-value="props.filters.context_id ? String(props.filters.context_id) : 'all'"
                @update:model-value="emit('contextChange', $event === 'all' ? undefined : $event)"
            >
                <SelectTrigger><SelectValue :placeholder="allContextsLabel(props.filters.context)" /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">{{ allContextsLabel(props.filters.context) }}</SelectItem>
                    <SelectItem v-for="option in props.contextOptions" :key="option.id" :value="String(option.id)">
                        {{ option.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>
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
