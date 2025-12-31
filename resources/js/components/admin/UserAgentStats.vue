<script lang="ts" setup>
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import type { UserAgentEntry, UserAgentStats } from '@/types/stats';
import { Info } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import StatsSwitcher from '../stats/StatsSwitcher.vue';

const { t } = useI18n();

const props = defineProps<{
    stats: UserAgentStats;
}>();

type ViewType = 'unique' | 'added';

const currentView = ref<ViewType>('unique');

const switcherOptions = [
    { value: 'unique', label: t('admin.stats.user_agents.unique_label') },
    { value: 'added', label: t('admin.stats.user_agents.added_label') },
];

const displayAgents = computed<UserAgentEntry[]>(() => {
    return currentView.value === 'unique' ? props.stats.last_unique : props.stats.last_added;
});
</script>

<template>
    <Card class="flex h-full flex-col">
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">{{ t('admin.stats.user_agents.title') }}</CardTitle>
            <StatsSwitcher v-model="currentView" :options="switcherOptions" />
        </CardHeader>
        <CardContent class="flex-1">
            <div v-if="displayAgents.length === 0" class="flex h-full items-center justify-center text-sm text-muted-foreground">
                {{ t('admin.stats.user_agents.no_data') }}
            </div>
            <ul v-else class="space-y-3">
                <li v-for="agent in displayAgents" :key="agent.id" class="flex items-center justify-between text-sm">
                    <div class="flex items-center gap-2 overflow-hidden">
                        <span :title="agent.name" class="truncate font-medium">{{ agent.name }}</span>
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger>
                                    <Info class="h-4 w-4 text-muted-foreground" />
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p class="max-w-xs text-xs break-all">{{ agent.name }}</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </div>
                </li>
            </ul>
        </CardContent>
    </Card>
</template>
