<script lang="ts" setup>
import SubscriptionInfoTooltip from '@/components/admin/SubscriptionInfoTooltip.vue';
import { useI18nNs } from '@/composables/useI18nNs';
import { NewsletterSubscription } from '@/types/admin.types';

const { t } = await useI18nNs('admin');

defineProps<{
    subscriptions: NewsletterSubscription[];
}>();
</script>

<template>
    <div class="flex h-full flex-col">
        <h3 class="mb-2 text-sm font-medium">{{ t('admin.dashboard.recent_subscriptions') }}</h3>
        <div v-if="subscriptions.length > 0" class="flex flex-col gap-2">
            <div
                v-for="subscription in subscriptions"
                :key="subscription.email"
                class="flex items-center justify-between rounded-lg border border-sidebar-border/50 bg-sidebar-accent/20 p-2 text-sm"
            >
                <span class="truncate">{{ subscription.email }}</span>
                <SubscriptionInfoTooltip :subscriptions="subscription.subscriptions" />
            </div>
        </div>
        <div v-else class="flex flex-1 items-center justify-center text-sm text-muted-foreground">{{ t('admin.dashboard.no_subscriptions') }}</div>
    </div>
</template>
