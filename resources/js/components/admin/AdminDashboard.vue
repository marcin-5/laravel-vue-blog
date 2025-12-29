<script lang="ts" setup>
import RecentSubscriptions from '@/components/admin/RecentSubscriptions.vue';
import type { NewsletterSubscription } from '@/types/admin.types';
import PlaceholderPattern from '../PlaceholderPattern.vue';

defineProps<{
    newsletterSubscriptions?: NewsletterSubscription[];
}>();
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div
                v-if="newsletterSubscriptions"
                class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            >
                <RecentSubscriptions :subscriptions="newsletterSubscriptions" />
            </div>
            <div v-else class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <PlaceholderPattern />
            </div>
            <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <PlaceholderPattern />
            </div>
            <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <PlaceholderPattern />
            </div>
        </div>
        <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
            <PlaceholderPattern />
        </div>
    </div>
</template>
