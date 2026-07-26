<script lang="ts" setup>
import { VISITOR_SORT_OPTIONS } from '@/constants/stats';
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
            <StatsFilters
                v-model="model"
                :blog-filter-label="blogFilterLabel"
                :blog-options="blogOptions"
                :show-blog-filter="true"
                :show-blogger-filter="false"
                :show-group-by-filter="true"
                :sort-options="[...VISITOR_SORT_OPTIONS]"
            />
        </template>
        <StatsTable :columns="columns" :data="data" :info-key="visitorInfoKey" row-key="row_id" />
    </StatsSection>
</template>
