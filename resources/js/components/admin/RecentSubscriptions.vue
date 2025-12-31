<script lang="ts" setup>
import SubscriptionInfoTooltip from '@/components/admin/SubscriptionInfoTooltip.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { NewsletterSubscription } from '@/types/admin.types';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps<{
    subscriptions: NewsletterSubscription[];
}>();
</script>

<template>
    <Card class="flex h-full flex-col">
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">{{ t('admin.dashboard.recent_subscriptions') }}</CardTitle>
        </CardHeader>
        <CardContent class="flex-1">
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
            <div v-else class="flex h-full items-center justify-center text-sm text-muted-foreground">
                {{ t('admin.dashboard.no_subscriptions') }}
            </div>
        </CardContent>
    </Card>
</template>
