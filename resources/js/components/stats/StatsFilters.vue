<script lang="ts" setup>
import type { BlogOption, FilterState, StatsRange, UserOption } from '@/types/stats';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import FilterSelect from './FilterSelect.vue';

interface Props {
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

const props = withDefaults(defineProps<Props>(), {
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

const model = defineModel<FilterState>({ required: true });

const { t } = useI18n();

const ranges = computed(
    () =>
        [
            { value: 'today', label: t('stats.filter_options.today') },
            { value: 'week', label: t('stats.filter_options.week') },
            { value: 'month', label: t('stats.filter_options.month') },
            { value: 'half_year', label: t('stats.filter_options.half_year') },
            { value: 'year', label: t('stats.filter_options.year') },
            { value: 'lifetime', label: t('stats.filter_options.lifetime') },
        ] as { value: StatsRange; label: string }[],
);

const sizes = computed(() => [
    { value: 5, label: '5' },
    { value: 10, label: '10' },
    { value: 20, label: '20' },
    { value: 0, label: t('stats.filter_options.size_all') },
]);

const groupOptions = computed(() => [
    { value: 'visitor_id', label: t('stats.filter_options.visitor_id') },
    { value: 'fingerprint', label: t('stats.filter_options.fingerprint') },
]);

const visitorTypeOptions = computed(() => [
    { value: 'all', label: t('stats.visitor_types.all') },
    { value: 'bots', label: t('stats.visitor_types.bots') },
    { value: 'anonymous', label: t('stats.visitor_types.anonymous') },
    { value: 'markdown', label: t('stats.visitor_types.markdown') },
]);

const translatedSortOptions = computed(() =>
    props.sortOptions.map((opt) => ({
        ...opt,
        label: t(`stats.sort_options.${opt.value}`, opt.label),
    })),
);

const translatedBloggers = computed(() => props.bloggers?.map((b) => ({ value: b.id, label: b.name })) || []);

const translatedBlogOptions = computed(() => [
    { value: 'all', label: props.blogFilterLabel || t('stats.filters.all') },
    ...props.blogOptions.map((b) => ({ value: b.id, label: b.name })),
]);
</script>

<template>
    <div class="flex flex-wrap items-end gap-3 rounded-xl border border-sidebar-border/70 p-4">
        <FilterSelect v-if="showRangeFilter" v-model="model.range" :options="ranges" :label="t('stats.filters.range')" />

        <FilterSelect v-model="model.sort" :options="translatedSortOptions" :label="t('stats.filters.sort')" />

        <FilterSelect v-model="model.size" :options="sizes" :label="t('stats.filters.size')" />

        <FilterSelect
            v-if="showBloggerFilter"
            v-model="model.blogger_id"
            :options="translatedBloggers"
            :label="t('stats.filters.blogger')"
            min-width="min-w-48"
            :placeholder="t('stats.filters.all')"
        />

        <FilterSelect
            v-if="showBlogFilter"
            v-model="model.blog_id"
            :options="translatedBlogOptions"
            :placeholder="blogFilterLabel"
            :label="t('stats.filters.blog')"
            min-width="min-w-48"
        />

        <FilterSelect
            v-if="showVisitorTypeFilter"
            v-model="model.visitor_type"
            :options="visitorTypeOptions"
            :label="t('stats.filters.visitor_type')"
        />

        <FilterSelect
            v-if="showGroupByFilter"
            v-model="model.group_by"
            :options="groupOptions"
            :label="t('stats.filters.identify_by')"
        />
    </div>
</template>
