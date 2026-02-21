<script lang="ts" setup>
import { useI18n } from 'vue-i18n';
import FilterSelect from './FilterSelect.vue';

type Range = 'today' | 'week' | 'month' | 'half_year' | 'year';
type UserOption = { id: number; name: string };
type BlogOption = { id: number; name: string };

interface Props {
    selectedRange: Range;
    selectedSort: string;
    selectedSize: number;
    selectedBlogger?: number | null;
    selectedBlog?: number | null;
    selectedGroupBy?: 'visitor_id' | 'fingerprint';
    selectedVisitorType?: 'all' | 'bots' | 'anonymous';
    bloggers?: UserOption[];
    blogOptions: BlogOption[];
    showBloggerFilter?: boolean;
    showBlogFilter?: boolean;
    showGroupByFilter?: boolean;
    showVisitorTypeFilter?: boolean;
    showRangeFilter?: boolean;
    blogFilterLabel?: string;
    sortOptions: { value: string; label: string }[];
}

withDefaults(defineProps<Props>(), {
    showBlogFilter: true,
    showGroupByFilter: false,
    showVisitorTypeFilter: false,
    showRangeFilter: true,
    sortOptions: () => [
        { value: 'views_desc', label: 'Views ↓' },
        { value: 'views_asc', label: 'Views ↑' },
        { value: 'name_asc', label: 'Name A→Z' },
        { value: 'name_desc', label: 'Name Z→A' },
    ],
});

const emit = defineEmits<{
    'update:selectedRange': [value: Range];
    'update:selectedSort': [value: string];
    'update:selectedSize': [value: number];
    'update:selectedBlogger': [value: number | null | undefined];
    'update:selectedBlog': [value: number | null | undefined];
    'update:selectedGroupBy': [value: 'visitor_id' | 'fingerprint'];
    'update:selectedVisitorType': [value: 'all' | 'bots' | 'anonymous'];
}>();

const { t } = useI18n();

const ranges: { value: Range; label: string }[] = [
    { value: 'today', label: 'Today' },
    { value: 'week', label: 'Last week' },
    { value: 'month', label: 'Last month' },
    { value: 'half_year', label: 'Last 6 months' },
    { value: 'year', label: 'Last year' },
];

const sizes = [
    { value: 5, label: '5' },
    { value: 10, label: '10' },
    { value: 20, label: '20' },
    { value: 0, label: 'All' },
];

const groupOptions = [
    { value: 'visitor_id', label: 'Visitor ID' },
    { value: 'fingerprint', label: 'Fingerprint' },
];

const visitorTypeOptions = [
    { value: 'all', label: t('admin.stats.visitor_types.all') },
    { value: 'bots', label: t('admin.stats.visitor_types.bots') },
    { value: 'anonymous', label: t('admin.stats.visitor_types.anonymous') },
];
</script>

<template>
    <div class="flex flex-wrap items-end gap-3 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
        <FilterSelect
            v-if="showRangeFilter"
            :model-value="selectedRange"
            :options="ranges"
            label="Time range"
            @update:model-value="emit('update:selectedRange', $event as Range)"
        />

        <FilterSelect
            :model-value="selectedSort"
            :options="sortOptions"
            label="Sort"
            @update:model-value="emit('update:selectedSort', $event as string)"
        />

        <FilterSelect
            :model-value="selectedSize"
            :options="sizes"
            label="Items"
            @update:model-value="emit('update:selectedSize', $event as number)"
        />

        <FilterSelect
            v-if="showBloggerFilter"
            :model-value="selectedBlogger"
            :options="bloggers?.map((b) => ({ value: b.id, label: b.name })) || []"
            label="Blogger"
            min-width="min-w-48"
            placeholder="All"
            @update:model-value="emit('update:selectedBlogger', $event as number | null | undefined)"
        />

        <FilterSelect
            v-if="showBlogFilter"
            :model-value="selectedBlog"
            :options="[{ value: 'all', label: blogFilterLabel || 'All' }, ...blogOptions.map((b) => ({ value: b.id, label: b.name }))]"
            :placeholder="blogFilterLabel"
            label="Blog"
            min-width="min-w-48"
            @update:model-value="emit('update:selectedBlog', ($event === 'all' ? null : $event) as number | null | undefined)"
        />

        <FilterSelect
            v-if="showVisitorTypeFilter"
            :model-value="selectedVisitorType"
            :options="visitorTypeOptions"
            label="Visitor type"
            @update:model-value="emit('update:selectedVisitorType', $event as 'all' | 'bots' | 'anonymous')"
        />

        <FilterSelect
            v-if="showGroupByFilter"
            :model-value="selectedGroupBy"
            :options="groupOptions"
            label="Identify by"
            @update:model-value="emit('update:selectedGroupBy', $event as 'visitor_id' | 'fingerprint')"
        />
    </div>
</template>
