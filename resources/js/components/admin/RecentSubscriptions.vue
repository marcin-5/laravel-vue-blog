<script lang="ts" setup>
import SubscriptionInfoTooltip from '@/components/admin/SubscriptionInfoTooltip.vue';

interface NewsletterSubscription {
    email: string;
    subscriptions: {
        blog: string;
        frequency: string;
    }[];
}

defineProps<{
    subscriptions: NewsletterSubscription[];
}>();
</script>

<template>
    <div class="flex h-full flex-col">
        <h3 class="mb-2 text-sm font-medium">Najnowsze subskrypcje</h3>
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
        <div v-else class="flex flex-1 items-center justify-center text-sm text-muted-foreground">Brak subskrypcji</div>
    </div>
</template>
