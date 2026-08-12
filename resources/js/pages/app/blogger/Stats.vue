<script lang="ts" setup>
import StatsPage from '@/components/stats/StatsPage.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps, BreadcrumbItem } from '@/types';
import { mapStatsPageProps, type StatsPageConfig, type StatsPageInertiaProps } from '@/types/stats';
import { usePage } from '@inertiajs/vue3';

import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const page = usePage<AppPageProps>();
const isAdmin = page.props.auth.user?.role === 'admin';

const props = defineProps<StatsPageInertiaProps>();

const statsPageConfig: StatsPageConfig = {
    routeName: 'blogger.stats.index',
    showBloggerFilter: isAdmin,
};

const statsPageProps = computed(() => mapStatsPageProps(props, statsPageConfig));

const breadcrumbs: BreadcrumbItem[] = [{ title: t('blogger.stats_title'), href: '/blogger/stats' }];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <StatsPage v-bind="statsPageProps" />
    </AppLayout>
</template>
