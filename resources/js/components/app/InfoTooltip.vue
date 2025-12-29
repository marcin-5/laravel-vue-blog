<script lang="ts" setup>
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Info } from 'lucide-vue-next';

interface Props {
    title: string;
    columns: string[];
    rows: Record<string, any>[];
}

defineProps<Props>();
</script>

<template>
    <TooltipProvider>
        <Tooltip :delay-duration="0">
            <TooltipTrigger as-child>
                <button class="ml-2 flex items-center justify-center text-muted-foreground hover:text-foreground">
                    <Info class="h-4 w-4" />
                </button>
            </TooltipTrigger>
            <TooltipContent class="max-w-xs" side="top">
                <div class="space-y-2">
                    <p class="font-semibold">{{ title }}</p>
                    <div :style="{ gridTemplateColumns: `repeat(${columns.length}, minmax(0, 1fr))` }" class="grid gap-x-4 text-xs">
                        <template v-for="column in columns" :key="column">
                            <div class="font-medium text-muted-foreground">{{ column }}</div>
                        </template>
                        <template v-for="(row, index) in rows" :key="index">
                            <template v-for="column in Object.keys(row)" :key="column">
                                <div>{{ row[column] }}</div>
                            </template>
                        </template>
                    </div>
                </div>
            </TooltipContent>
        </Tooltip>
    </TooltipProvider>
</template>
