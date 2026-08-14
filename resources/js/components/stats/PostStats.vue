<script lang="ts" setup>
import type { FilterState, PostRow, StatsFilterConfig } from '@/types/stats';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import StatsFilters from './StatsFilters.vue';
import StatsSection from './StatsSection.vue';
import StatsTable from './StatsTable.vue';

interface Props {
    data: PostRow[];
    filterConfig: StatsFilterConfig;
}

defineProps<Props>();

const model = defineModel<FilterState>({ required: true });
const { t } = useI18n();

const columns = computed(() => [
    { key: 'title', label: t('stats.columns.title') },
    { key: 'views', label: t('stats.columns.consent_views') },
    { key: 'bot_views', label: t('stats.columns.bot_views') },
    { key: 'anonymous_views', label: t('stats.columns.anonymous_views') },
    { key: 'markdown_views', label: t('stats.columns.markdown_views') },
]);
</script>

<template>
    <StatsSection :title="t('stats.sections.post_views')">
        <template #filters>
            <StatsFilters v-model="model" :config="filterConfig" />
        </template>
        <StatsTable :columns="columns" :data="data" row-key="post_id" />
    </StatsSection>
</template>
