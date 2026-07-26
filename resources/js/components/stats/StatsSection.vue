<script lang="ts" setup>
interface Props {
    title: string;
    showSeparator?: boolean;
}

withDefaults(defineProps<Props>(), {
    showSeparator: true,
});
</script>

<template>
    <div class="flex flex-col gap-4">
        <h2 class="text-lg font-medium text-sidebar-foreground">{{ title }}</h2>

        <div v-if="$slots.filters" class="flex flex-col gap-4">
            <slot name="filters" />
        </div>

        <slot />

        <div v-if="showSeparator" class="mt-2 h-px w-full bg-sidebar-border/70 dark:bg-sidebar-border"></div>
    </div>
</template>
