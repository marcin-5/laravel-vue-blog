<script lang="ts" setup>
import StatsPage from '@/components/stats/StatsPage.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { mapStatsPageProps, type StatsPageConfig, type StatsPageInertiaProps } from '@/types/stats';
import { Head } from '@inertiajs/vue3';

import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps<StatsPageInertiaProps>();

const statsPageConfig: StatsPageConfig = {
    routeName: 'admin.stats.index',
    showBloggerFilter: true,
    showBloggerColumn: true,
};

const statsPageProps = computed(() => mapStatsPageProps(props, statsPageConfig));

const breadcrumbs: BreadcrumbItem[] = [{ title: t('admin.stats.title'), href: '/admin/stats' }];
</script>

<template>
    <Head :title="t('admin.stats.title')">
        <!-- Prevent indexing for non-public pages -->
        <template>
            <meta content="noindex, nofollow" name="robots" />
        </template>
    </Head>

    <AppLayout :breadcrumbs="breadcrumbs">
        <StatsPage v-bind="statsPageProps" />
    </AppLayout>
</template>
