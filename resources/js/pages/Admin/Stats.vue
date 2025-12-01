<script lang="ts" setup>
import StatsPage from '@/components/stats/StatsPage.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

type Range = 'week' | 'month' | 'half_year' | 'year';
type BlogRow = { blog_id: number; name: string; owner_id: number; owner_name: string; views: number };
type PostRow = { post_id: number; title: string; views: number };
type UserOption = { id: number; name: string };
type BlogOption = { id: number; name: string };

interface Props {
    filters: {
        range: Range;
        sort: string;
        size: number;
        blogger_id?: number | null;
        blog_id?: number | null;
    };
    blogs: BlogRow[];
    posts: PostRow[];
    bloggers: UserOption[];
    blogOptions: BlogOption[];
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
            :blog-options="blogOptions"
            :bloggers="bloggers"
            :blogs="blogs"
            :filters="filters"
            :posts="posts"
            :show-blogger-column="true"
            :show-blogger-filter="true"
            blog-filter-label="All"
            route-name="admin.stats.index"
        />
    </AppLayout>
</template>
