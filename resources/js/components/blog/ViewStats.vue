<script lang="ts" setup>
defineProps({
    total: {
        type: Number,
        required: true,
    },
    unique: {
        type: Number,
        required: false,
        default: undefined,
    },
});
</script>

<template>
    <div class="inline-flex items-center gap-2 rounded-full bg-secondary px-3 py-1 text-xs font-medium text-secondary-foreground">
        <span class="inline-block h-2 w-2 rounded-full bg-foreground opacity-80"></span>
        <span>
            Odsłony: {{ total.toLocaleString() }}
            <template v-if="unique !== undefined"> (unikalne: {{ Number(unique).toLocaleString() }})</template>
        </span>
    </div>
</template>
