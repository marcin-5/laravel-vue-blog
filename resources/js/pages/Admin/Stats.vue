<script lang="ts" setup>
import StatsPage from '@/components/stats/StatsPage.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { BlogOption, BlogRow, FilterState, PostRow, UserOption, VisitorRow } from '@/types/stats';
import { Head } from '@inertiajs/vue3';

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

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Statistics', href: '/admin/stats' }];
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
            blog-filter-label="All"
            route-name="admin.stats.index"
        />
    </AppLayout>
</template>
