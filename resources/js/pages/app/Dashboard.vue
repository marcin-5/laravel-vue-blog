<script lang="ts" setup>
import AdminDashboard from '@/components/admin/AdminDashboard.vue';
import UserDashboard from '@/components/app/UserDashboard.vue';
import BloggerDashboard from '@/components/blogger/BloggerDashboard.vue';
import { useDashboardView } from '@/composables/useDashboardView';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type NewsletterSubscription } from '@/types/admin.types';
import { Head } from '@inertiajs/vue3';

defineProps<{
    newsletterSubscriptions?: NewsletterSubscription[];
}>();

const { currentView } = useDashboardView();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <AdminDashboard v-if="currentView === 'admin'" :newsletter-subscriptions="newsletterSubscriptions" />
        <BloggerDashboard v-else-if="currentView === 'blogger'" />
        <UserDashboard v-else />
    </AppLayout>
</template>
