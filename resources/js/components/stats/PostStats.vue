<script lang="ts" setup>
import { POST_SORT_OPTIONS } from '@/constants/stats';
import type { BlogOption, FilterState, PostRow, UserOption } from '@/types/stats';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import StatsFilters from './StatsFilters.vue';
import StatsSection from './StatsSection.vue';
import StatsTable from './StatsTable.vue';

interface Props {
    data: PostRow[];
    blogOptions: BlogOption[];
    bloggers?: UserOption[];
    showBloggerFilter?: boolean;
    blogFilterLabel?: string;
}

const props = withDefaults(defineProps<Props>(), {
    showBloggerFilter: false,
    blogFilterLabel: undefined,
    bloggers: () => [],
});

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
            <StatsFilters
                v-model="model"
                :blog-filter-label="blogFilterLabel"
                :blog-options="blogOptions"
                :bloggers="bloggers"
                :show-blogger-filter="showBloggerFilter"
                :sort-options="[...POST_SORT_OPTIONS]"
            />
        </template>
        <StatsTable :columns="columns" :data="data" row-key="post_id" />
    </StatsSection>
</template>
