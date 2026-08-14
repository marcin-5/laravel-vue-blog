<script lang="ts" setup>
import type { FilterState, StatsRange, StatsFilterConfig } from '@/types/stats';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import FilterSelect from './FilterSelect.vue';

const props = defineProps<{ config: StatsFilterConfig }>();

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
    props.config.sortOptions.map((opt) => ({
        ...opt,
        label: t(`stats.sort_options.${opt.value}`, opt.label),
    })),
);

const translatedBloggers = computed(() => props.config.bloggers?.map((blogger) => ({ value: blogger.id, label: blogger.name })) ?? []);

const translatedBlogOptions = computed(() => [
    { value: 'all', label: props.config.blogFilterLabel || t('stats.filters.all') },
    ...props.config.blogOptions.map((blog) => ({ value: blog.id, label: blog.name })),
]);
</script>

<template>
    <div class="flex flex-wrap items-end gap-3 rounded-xl border border-sidebar-border/70 p-4">
        <FilterSelect v-if="config.showRangeFilter !== false" v-model="model.range" :options="ranges" :label="t('stats.filters.range')" />

        <FilterSelect v-model="model.sort" :options="translatedSortOptions" :label="t('stats.filters.sort')" />

        <FilterSelect v-model="model.size" :options="sizes" :label="t('stats.filters.size')" />

        <FilterSelect
            v-if="config.showBloggerFilter"
            v-model="model.blogger_id"
            :options="translatedBloggers"
            :label="t('stats.filters.blogger')"
            min-width="min-w-48"
            :placeholder="t('stats.filters.all')"
        />

        <FilterSelect
            v-if="config.showBlogFilter !== false"
            v-model="model.blog_id"
            :options="translatedBlogOptions"
            :placeholder="config.blogFilterLabel"
            :label="t('stats.filters.blog')"
            min-width="min-w-48"
        />

        <FilterSelect
            v-if="config.showVisitorTypeFilter"
            v-model="model.visitor_type"
            :options="visitorTypeOptions"
            :label="t('stats.filters.visitor_type')"
        />

        <FilterSelect v-if="config.showGroupByFilter" v-model="model.group_by" :options="groupOptions" :label="t('stats.filters.identify_by')" />
    </div>
</template>
