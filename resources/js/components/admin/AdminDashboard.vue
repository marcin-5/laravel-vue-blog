<script lang="ts" setup>
import BotStats from '@/components/admin/BotStats.vue';
import FontUsageStats from '@/components/admin/FontUsageStats.vue';
import RecentSubscriptions from '@/components/admin/RecentSubscriptions.vue';
import UserAgentStats from '@/components/admin/UserAgentStats.vue';
import StatsSkeleton from '@/components/StatsSkeleton.vue';
import type { NewsletterSubscription } from '@/types/admin.types';
import type { BotStats as BotStatsType, FontUsageStats as FontUsageStatsType, UserAgentStats as UserAgentStatsType } from '@/types/stats';
import PlaceholderPattern from '../PlaceholderPattern.vue';

defineProps<{
    newsletterSubscriptions?: NewsletterSubscription[];
    userAgentStats?: UserAgentStatsType;
    botStats?: BotStatsType;
    fontUsageStats?: FontUsageStatsType | null;
}>();
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div v-if="newsletterSubscriptions" class="relative aspect-video overflow-hidden">
                <RecentSubscriptions :subscriptions="newsletterSubscriptions" />
            </div>
            <StatsSkeleton v-else class="aspect-video" />

            <div v-if="userAgentStats" class="relative aspect-video overflow-hidden">
                <UserAgentStats :stats="userAgentStats" />
            </div>
            <StatsSkeleton v-else class="aspect-video" />

            <div v-if="botStats" class="relative aspect-video overflow-hidden">
                <BotStats :stats="botStats" />
            </div>
            <StatsSkeleton v-else class="aspect-video" />
        </div>
        <div v-if="fontUsageStats" class="relative rounded-xl">
            <FontUsageStats :stats="fontUsageStats" />
        </div>
        <StatsSkeleton v-else-if="fontUsageStats === undefined" class="min-h-72" />
        <div class="relative min-h-screen flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min">
            <PlaceholderPattern />
        </div>
    </div>
</template>
