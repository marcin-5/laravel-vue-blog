<script lang="ts" setup>
import StatsPage from '@/components/stats/StatsPage.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { BlogOption, BlogRow, FilterState, PostRow, VisitorRow } from '@/types/stats';
import { Head } from '@inertiajs/vue3';

interface Props {
    blogFilters: FilterState;
    postFilters: FilterState;
    visitorFilters: FilterState;
    blogs: BlogRow[];
    posts: PostRow[];
    visitors: VisitorRow[];
    blogOptions: BlogOption[];
    visitorBlogOptions: BlogOption[];
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Statistics', href: '/blogger/stats' }];
</script>

<template>
    <Head title="Statistics">
        <!-- Prevent indexing for non-public pages -->
        <template>
            <meta content="noindex, nofollow" name="robots" />
        </template>
    </Head>

    <AppLayout :breadcrumbs="breadcrumbs">
        <StatsPage
            :blog-filters="blogFilters"
            :blog-options="blogOptions"
            :blogs="blogs"
            :post-filters="postFilters"
            :posts="posts"
            :visitor-blog-options="visitorBlogOptions"
            :visitor-filters="visitorFilters"
            :visitors="visitors"
            blog-filter-label="All my blogs"
            route-name="blogger.stats.index"
        />
    </AppLayout>
</template>
