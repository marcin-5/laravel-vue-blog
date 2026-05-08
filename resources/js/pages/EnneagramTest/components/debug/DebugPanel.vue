<script lang="ts" setup>
defineProps<{
    active?: boolean;
    visible?: boolean;
    title: string;
}>();
</script>

<template>
    <div
        v-if="visible !== false"
        :class="[
            'rounded-md border p-4 text-sm transition-colors',
            active ? 'border-secondary-foreground bg-secondary' : 'border-border bg-muted/40 text-muted-foreground',
        ]"
    >
        <div :class="active ? 'text-primary' : 'text-foreground'" class="mb-2 font-semibold">
            {{ title }}
        </div>
        <slot />
    </div>
</template>
