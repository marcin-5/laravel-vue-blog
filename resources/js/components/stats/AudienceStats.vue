<script lang="ts" setup>
import type { FilterState, StatsFilterConfig, VisitorRow } from '@/types/stats';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import StatsFilters from './StatsFilters.vue';
import StatsSection from './StatsSection.vue';
import StatsTable from './StatsTable.vue';

interface Props {
    data: VisitorRow[];
    filterConfig: StatsFilterConfig;
}

defineProps<Props>();

const model = defineModel<FilterState>({ required: true });
const { t } = useI18n();

const columns = computed(() => [
    {
        key: 'visitor_label',
        label: model.value.group_by === 'fingerprint' ? t('stats.columns.fingerprint') : t('stats.columns.visitor'),
        hasInfo: true,
    },
    { key: 'blog_views', label: t('stats.columns.blog_views') },
    { key: 'post_views', label: t('stats.columns.post_views') },
    { key: 'lifetime_views', label: t('stats.columns.lifetime_visits') },
]);

const visitorInfoKey = computed(() => 'user_agent');
</script>

<template>
    <StatsSection :title="t('stats.sections.consent_views')">
        <template #filters>
            <StatsFilters v-model="model" :config="filterConfig" />
        </template>
        <StatsTable :columns="columns" :data="data" :info-key="visitorInfoKey" row-key="row_id" />
    </StatsSection>
</template>
