<script lang="ts" setup>
defineProps<{
    class?: string;
}>();
</script>

<template>
    <div :class="['border-b border-gray-400 dark:border-gray-700', $props.class]"></div>
</template>
