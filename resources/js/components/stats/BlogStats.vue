<script lang="ts" setup>
import { BLOG_SORT_OPTIONS } from '@/constants/stats';
import type { BlogOption, BlogRow, FilterState, UserOption } from '@/types/stats';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import StatsFilters from './StatsFilters.vue';
import StatsSection from './StatsSection.vue';
import StatsTable from './StatsTable.vue';

interface Props {
    data: BlogRow[];
    blogOptions: BlogOption[];
    bloggers?: UserOption[];
    showBloggerFilter?: boolean;
    showBloggerColumn?: boolean;
    blogFilterLabel?: string;
}

const props = withDefaults(defineProps<Props>(), {
    showBloggerFilter: false,
    showBloggerColumn: false,
    blogFilterLabel: undefined,
    bloggers: () => [],
});

const model = defineModel<FilterState>({ required: true });
const { t } = useI18n();

const columns = computed(() => [
    { key: 'name', label: t('stats.columns.blog') },
    { key: 'owner_name', label: t('stats.columns.blogger'), visible: props.showBloggerColumn },
    { key: 'views', label: t('stats.columns.blog_views') },
    { key: 'post_views', label: t('stats.columns.post_views') },
    { key: 'markdown_views', label: t('stats.columns.markdown_views') },
]);
</script>

<template>
    <StatsSection :title="t('stats.sections.blog_views')">
        <template #filters>
            <StatsFilters
                v-model="model"
                :blog-filter-label="blogFilterLabel"
                :blog-options="blogOptions"
                :bloggers="bloggers"
                :show-blog-filter="false"
                :show-blogger-filter="showBloggerFilter"
                :sort-options="[...BLOG_SORT_OPTIONS]"
            />
        </template>
        <StatsTable :columns="columns" :data="data" row-key="blog_id" />
    </StatsSection>
</template>
