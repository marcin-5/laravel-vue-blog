<script lang="ts" setup>
defineProps({
    total: {
        type: Number,
        required: true,
    },
});
</script>

<template>
    <div
        class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-200"
    >
        <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
        <span>Odsłony: {{ total.toLocaleString() }}</span>
    </div>
</template>
