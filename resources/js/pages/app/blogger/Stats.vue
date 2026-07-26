<script lang="ts" setup>
import StatsPage from '@/components/stats/StatsPage.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { BlogOption, BlogRow, FilterState, PostRow, UserOption, VisitorRow } from '@/types/stats';
import { usePage } from '@inertiajs/vue3';

import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const page = usePage();
const isAdmin = page.props.auth.user?.role === 'admin';

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
    <AppLayout :breadcrumbs="breadcrumbs">
        <StatsPage
            :config="{
                routeName: 'blogger.stats.index',
                showBloggerFilter: isAdmin,
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
                visitorBlogOptions: visitorBlogOptions,
            }"
        />
    </AppLayout>
</template>
