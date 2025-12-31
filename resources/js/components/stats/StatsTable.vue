<script generic="T extends Record<string, any>" lang="ts" setup>
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Info } from 'lucide-vue-next';

interface Column {
    key: string;
    label: string;
    visible?: boolean;
    hasInfo?: boolean;
}

interface Props {
    title?: string;
    columns: Column[];
    data: T[];
    rowKey: string;
    infoKey?: string;
}

defineProps<Props>();

function getVisibleColumns(columns: Column[]): Column[] {
    return columns.filter((col) => col.visible !== false);
}
</script>

<template>
    <div class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
        <h2 v-if="title" class="mb-3 text-lg font-semibold">{{ title }}</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-sidebar-border/70 text-xs text-muted-foreground uppercase dark:border-sidebar-border">
                    <tr>
                        <th v-for="col in getVisibleColumns(columns)" :key="col.key" class="py-2 pr-4">
                            {{ col.label }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in data" :key="row[rowKey]" class="border-b border-sidebar-border/70 last:border-b-0 dark:border-sidebar-border">
                        <td v-for="col in getVisibleColumns(columns)" :key="col.key" class="py-2 pr-4">
                            <div class="flex items-center gap-2">
                                <span>{{ row[col.key] }}</span>
                                <TooltipProvider v-if="col.hasInfo && infoKey && row[infoKey]">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Info class="h-4 w-4 text-muted-foreground" />
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p class="max-w-xs text-xs break-all">{{ row[infoKey] }}</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!data.length">
                        <td :colspan="getVisibleColumns(columns).length" class="py-4 text-center text-muted-foreground">No data</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
