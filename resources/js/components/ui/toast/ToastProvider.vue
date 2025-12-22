<script lang="ts" setup>
import { ToastProvider } from 'reka-ui';

const props = defineProps<{
  label?: string
  duration?: number
  swipeDirection?: 'up' | 'down' | 'left' | 'right'
  swipeThreshold?: number
}>()
</script>

<template>
  <ToastProvider v-bind="props">
    <slot />
  </ToastProvider>
</template>
