<script lang="ts" setup>
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import type { BotStats, BotViewEntry } from '@/types/stats';
import { formatDateTime } from '@/utils/dateUtils';
import { Info } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import StatsSwitcher from '../stats/StatsSwitcher.vue';

const { t } = useI18n();

const props = defineProps<{
    stats: BotStats;
}>();

type ViewType = 'recent' | 'top';

const currentView = ref<ViewType>('recent');

const switcherOptions = computed(() => [
    { value: 'recent', label: t('admin.stats.bot_views.recent_label') },
    { value: 'top', label: t('admin.stats.bot_views.top_label') },
]);

const displayBots = computed<BotViewEntry[]>(() => {
    return currentView.value === 'recent' ? props.stats.last_seen : props.stats.top_hits;
});
</script>

<template>
    <Card class="flex h-full flex-col">
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">{{ t('admin.stats.bot_views.title') }}</CardTitle>
            <StatsSwitcher v-model="currentView" :options="switcherOptions" />
        </CardHeader>
        <CardContent class="flex-1 overflow-hidden">
            <div v-if="displayBots.length === 0" class="flex h-full items-center justify-center text-sm text-muted-foreground">
                {{ t('admin.stats.bot_views.no_data') }}
            </div>
            <ul v-else class="space-y-3">
                <li v-for="bot in displayBots" :key="bot.id" class="flex items-center justify-between text-sm">
                    <div class="flex items-center gap-2 overflow-hidden">
                        <span :title="bot.matched_fragment" class="truncate font-medium">{{ bot.matched_fragment }}</span>
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger>
                                    <Info class="h-4 w-4 text-muted-foreground" />
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p class="max-w-xs text-xs">{{ bot.name }}</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </div>
                    <div class="shrink-0 text-xs text-muted-foreground">
                        <template v-if="currentView === 'recent'">
                            {{ formatDateTime(bot.last_seen_at) }}
                        </template>
                        <template v-else>
                            {{ bot.hits }}
                        </template>
                    </div>
                </li>
            </ul>
        </CardContent>
        <CardFooter class="justify-end pt-0 text-[10px] text-muted-foreground">
            {{ t('admin.stats.bot_views.total_label', { total: stats.total_hits }) }}
        </CardFooter>
    </Card>
</template>
