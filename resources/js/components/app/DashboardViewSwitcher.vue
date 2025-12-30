<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { type DashboardView, useDashboardView } from '@/composables/useDashboardView';
import { ChevronDown } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { currentView, availableViews, setView } = useDashboardView();

const viewLabels: Record<DashboardView, string> = {
    admin: t('dashboard.view_switcher.admin'),
    blogger: t('dashboard.view_switcher.blogger'),
    user: t('dashboard.view_switcher.user'),
};

const currentLabel = computed(() => (currentView.value ? viewLabels[currentView.value] : t('dashboard.view_switcher.select')));
</script>

<template>
    <div v-if="availableViews.length > 0" class="flex items-center gap-2">
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button class="h-8 gap-1 px-2 font-normal" size="sm" variant="ghost">
                    <span class="text-muted-foreground">{{ currentLabel }}</span>
                    <ChevronDown class="h-4 w-4 text-muted-foreground" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start" class="w-48">
                <DropdownMenuItem v-for="view in availableViews" :key="view" :class="{ 'bg-accent': currentView === view }" @click="setView(view)">
                    {{ viewLabels[view] }}
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
</template>
