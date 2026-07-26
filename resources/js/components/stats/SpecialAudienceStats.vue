<script lang="ts" setup>
import { SPECIAL_VISITOR_SORT_OPTIONS } from '@/constants/stats';
import type { BlogOption, FilterState, VisitorRow } from '@/types/stats';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import StatsFilters from './StatsFilters.vue';
import StatsSection from './StatsSection.vue';
import StatsTable from './StatsTable.vue';

interface Props {
    data: VisitorRow[];
    blogOptions: BlogOption[];
    blogFilterLabel?: string;
}

withDefaults(defineProps<Props>(), {
    blogFilterLabel: undefined,
});

const model = defineModel<FilterState>({ required: true });
const { t } = useI18n();

const columns = computed(() => [
    { key: 'visitor_label', label: t('stats.columns.user_agent') },
    { key: 'blog_views', label: t('stats.columns.blog_views') },
    { key: 'post_views', label: t('stats.columns.post_views') },
    { key: 'lifetime_views', label: t('stats.columns.lifetime_visits') },
    { key: 'last_seen_at', label: t('stats.columns.last_seen'), isDate: true },
]);
</script>

<template>
    <StatsSection :title="t('stats.sections.special_views')" :show-separator="false">
        <template #filters>
            <StatsFilters
                v-model="model"
                :blog-filter-label="blogFilterLabel"
                :blog-options="blogOptions"
                :show-blog-filter="true"
                :show-blogger-filter="false"
                :show-range-filter="false"
                :show-visitor-type-filter="true"
                :sort-options="[...SPECIAL_VISITOR_SORT_OPTIONS]"
            />
        </template>
        <StatsTable :columns="columns" :data="data" row-key="row_id" />
    </StatsSection>
</template>
