<script lang="ts" setup>
import { cn } from '@/lib/utils';
import { ToastDescription, type ToastDescriptionProps } from 'reka-ui';
import { computed, type HTMLAttributes } from 'vue';

const props = defineProps<ToastDescriptionProps & { class?: HTMLAttributes['class'] }>()

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props

  return delegated
})
</script>

<template>
  <ToastDescription :class="cn('text-sm font-inter opacity-90', props.class)" v-bind="delegatedProps">
    <slot />
  </ToastDescription>
</template>
