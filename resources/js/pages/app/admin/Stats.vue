<script lang="ts" setup>
import StatsPage from '@/components/stats/StatsPage.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { BlogOption, BlogRow, FilterState, PostRow, UserOption, VisitorRow } from '@/types/stats';
import { Head } from '@inertiajs/vue3';

import { useI18n } from 'vue-i18n';

const { t } = useI18n();

interface Props {
    blogFilters: FilterState;
    postFilters: FilterState;
    visitorFilters: FilterState;
    blogs: BlogRow[];
    posts: PostRow[];
    visitors: VisitorRow[];
    bloggers: UserOption[];
    blogOptions: BlogOption[];
    postBlogOptions: BlogOption[];
    visitorBlogOptions: BlogOption[];
}

defineProps<Props>();

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
        <StatsPage
            :blog-filter-label="t('common.all')"
            :blog-filters="blogFilters"
            :blog-options="blogOptions"
            :bloggers="bloggers"
            :blogs="blogs"
            :post-blog-options="postBlogOptions"
            :post-filters="postFilters"
            :posts="posts"
            :show-blogger-column="true"
            :show-blogger-filter="true"
            :visitor-blog-options="visitorBlogOptions"
            :visitor-filters="visitorFilters"
            :visitors="visitors"
            route-name="admin.stats.index"
        />
    </AppLayout>
</template>
