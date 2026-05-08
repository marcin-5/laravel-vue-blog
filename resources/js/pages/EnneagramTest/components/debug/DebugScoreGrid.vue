<script lang="ts" setup>
defineProps<{
    scores: Record<string | number, number>;
    labelPrefix?: string;
    title?: string;
}>();
</script>

<template>
    <div class="mt-2">
        <div v-if="title" class="mb-2 font-semibold text-foreground underline">{{ title }}</div>
        <div class="grid grid-cols-3 gap-2">
            <div v-for="(score, key) in scores" :key="key" class="flex justify-between border-b border-border pr-2 pb-1">
                <span class="text-muted-foreground">{{ labelPrefix }}{{ key }}:</span>
                <span class="font-bold text-foreground">{{ score }}</span>
            </div>
        </div>
    </div>
</template>
