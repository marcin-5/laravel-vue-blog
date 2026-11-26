<script lang="ts" setup>
import type { HTMLAttributes } from 'vue';
import { cn } from '@/utils/classUtils';

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <div
    :class="cn('font-nav flex min-h-0 flex-1 flex-col gap-2 overflow-auto group-data-[collapsible=icon]:overflow-hidden', props.class)"
    data-sidebar="content"
    data-slot="sidebar-content"
  >
    <slot />
  </div>
</template>
