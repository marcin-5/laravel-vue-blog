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
    specialVisitorFilters: FilterState;
    blogs: BlogRow[];
    posts: PostRow[];
    visitorsFromPage: VisitorRow[];
    visitorsFromSpecial: VisitorRow[];
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
            :config="{
                routeName: 'admin.stats.index',
                showBloggerFilter: true,
                showBloggerColumn: true,
            }"
            :data="{
                blogs: blogs,
                posts: posts,
                visitorsFromPage: visitorsFromPage,
                visitorsFromSpecial: visitorsFromSpecial,
            }"
            :filters="{
                blog: blogFilters,
                post: postFilters,
                visitor: visitorFilters,
                specialVisitor: specialVisitorFilters,
            }"
            :options="{
                bloggers: bloggers,
                blogOptions: blogOptions,
                postBlogOptions: postBlogOptions,
                visitorBlogOptions: visitorBlogOptions,
            }"
        />
    </AppLayout>
</template>
