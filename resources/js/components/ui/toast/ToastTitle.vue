<script lang="ts" setup>
import { cn } from '@/lib/utils';
import { ToastTitle, type ToastTitleProps } from 'reka-ui';
import { computed, type HTMLAttributes } from 'vue';

const props = defineProps<ToastTitleProps & { class?: HTMLAttributes['class'] }>()

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props

  return delegated
})
</script>

<template>
  <ToastTitle :class="cn('text-md [&+div]:text-sm', props.class)" v-bind="delegatedProps">
    <slot />
  </ToastTitle>
</template>
