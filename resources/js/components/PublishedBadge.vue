<script lang="ts" setup>
defineProps<{ published: boolean }>();
</script>

<template>
    <span :class="published ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-700'" class="rounded-full px-2 py-0.5 text-xs">
        {{ published ? 'Published' : 'Draft' }}
    </span>
</template>
