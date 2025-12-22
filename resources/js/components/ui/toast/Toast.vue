<script lang="ts" setup>
import { cn } from '@/lib/utils';
import { ToastRoot, type ToastRootEmits, type ToastRootProps, useForwardPropsEmits } from 'reka-ui';
import { computed, type HTMLAttributes } from 'vue';
import { type ToastVariants, toastVariants } from '.';

const props = defineProps<ToastRootProps & { class?: HTMLAttributes['class']; variant?: ToastVariants['variant']; size?: ToastVariants['size'] }>()
const emits = defineEmits<ToastRootEmits>()

const delegatedProps = computed(() => {
  const { class: _, variant: __, size: ___, ...delegated } = props

  return delegated
})

const forwarded = useForwardPropsEmits(delegatedProps, emits)
</script>

<template>
  <ToastRoot
    :class="cn(toastVariants({ variant, size }), props.class)"
    v-bind="forwarded"
  >
    <slot />
  </ToastRoot>
</template>
