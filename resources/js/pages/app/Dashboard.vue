<script lang="ts" setup>
import AdminDashboard from '@/components/admin/AdminDashboard.vue';
import UserDashboard from '@/components/app/UserDashboard.vue';
import BloggerDashboard from '@/components/blogger/BloggerDashboard.vue';
import { useDashboardView } from '@/composables/useDashboardView';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { type NewsletterSubscription } from '@/types/admin.types';
import { type BlogStats, type PostsStats } from '@/types/stats';
import { Head } from '@inertiajs/vue3';

import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps<{
    newsletterSubscriptions?: NewsletterSubscription[];
    blogStats?: BlogStats[];
    postsStats?: PostsStats;
}>();

const { currentView } = useDashboardView();

const breadcrumbs: BreadcrumbItem[] = [{ title: t('dashboard.title'), href: '/dashboard' }];
</script>

<template>
    <Head :title="t('dashboard.title')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <AdminDashboard v-if="currentView === 'admin'" :newsletter-subscriptions="newsletterSubscriptions" />
        <BloggerDashboard v-else-if="currentView === 'blogger'" :blog-stats="blogStats || []" :posts-stats="postsStats" />
        <UserDashboard v-else />
    </AppLayout>
</template>
