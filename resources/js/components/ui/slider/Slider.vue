<script lang="ts" setup>
import { computed, type HTMLAttributes } from 'vue';
import {
    SliderRange,
    SliderRoot,
    type SliderRootEmits,
    type SliderRootProps,
    SliderThumb,
    SliderTrack,
    useForwardPropsEmits
} from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps<SliderRootProps & { class?: HTMLAttributes['class'] }>()
const emit = defineEmits<SliderRootEmits>()

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props

  return delegated
})

const forwarded = useForwardPropsEmits(delegatedProps, emit)
</script>

<template>
  <SliderRoot
    :class="cn(
      'relative flex w-full touch-none select-none items-center data-[orientation=vertical]:h-full data-[orientation=vertical]:w-1.5 data-[orientation=vertical]:flex-col',
      props.class,
    )"
    v-bind="forwarded"
  >
    <SliderTrack class="relative h-1.5 w-full grow overflow-hidden rounded-full bg-secondary data-[orientation=vertical]:w-1.5">
      <SliderRange class="absolute h-full bg-primary data-[orientation=vertical]:w-full" />
    </SliderTrack>
    <SliderThumb
      v-for="(_, key) in modelValue"
      :key="key"
      class="block h-5 w-5 rounded-full border-2 border-primary bg-background ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50"
    />
  </SliderRoot>
</template>
