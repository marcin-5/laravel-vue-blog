<script lang="ts" setup>
defineProps<{
    class?: string;
}>();
</script>

<template>
    <div :class="['border-b border-border', $props.class]"></div>
</template>
