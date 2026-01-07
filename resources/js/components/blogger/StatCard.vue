<script lang="ts" setup>
import type { Component } from 'vue';

defineProps<{
    title: string;
    value: string | number;
    icon: Component;
}>();
</script>

<template>
    <div class="rounded-xl border border-sidebar-border/70 bg-sidebar p-4 shadow-sm dark:border-sidebar-border">
        <div class="flex gap-2">
            <component :is="icon" class="h-4 w-4 text-muted-foreground" />
            <span class="align-top text-xs font-medium tracking-wider text-muted-foreground uppercase">{{ title }}</span>
            <span class="ml-auto text-2xl font-bold">{{ value }}</span>
        </div>
    </div>
</template>
