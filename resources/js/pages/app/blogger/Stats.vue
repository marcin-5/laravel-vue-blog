<script lang="ts" setup>
import StatsPage from '@/components/stats/StatsPage.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { BlogOption, BlogRow, FilterState, PostRow, UserOption, VisitorRow } from '@/types/stats';
import { Head, usePage } from '@inertiajs/vue3';

import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const page = usePage();
const isAdmin = page.props.auth.user.role === 'admin';

interface Props {
    blogFilters: FilterState;
    postFilters: FilterState;
    visitorFilters: FilterState;
    specialVisitorFilters: FilterState;
    blogs: BlogRow[];
    posts: PostRow[];
    visitorsFromPage: VisitorRow[];
    visitorsFromSpecial: VisitorRow[];
    blogOptions: BlogOption[];
    visitorBlogOptions?: BlogOption[];
    bloggers?: UserOption[];
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [{ title: t('blogger.stats_title'), href: '/blogger/stats' }];
</script>

<template>
    <Head :title="t('blogger.stats_title')">
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
            :post-filters="postFilters"
            :posts="posts"
            :show-blogger-filter="isAdmin"
            :special-visitor-filters="specialVisitorFilters"
            :visitor-blog-options="visitorBlogOptions"
            :visitor-filters="visitorFilters"
            :visitors-from-page="visitorsFromPage"
            :visitors-from-special="visitorsFromSpecial"
            route-name="blogger.stats.index"
        />
    </AppLayout>
</template>
