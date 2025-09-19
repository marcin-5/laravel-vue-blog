<script lang="ts" setup>
import type { HTMLAttributes } from 'vue';

interface Emits {
  (e: 'click'): void;
}

const props = defineProps<{
  label: string;
  selected?: boolean;
  class?: HTMLAttributes['class'];
}>();

const emit = defineEmits<Emits>();
</script>

<template>
  <button
    :class="[
      'rounded-full border px-3 py-1 text-sm transition-colors',
      selected
        ? 'border-blue-800 bg-blue-900 text-white'
        : 'border-gray-300 bg-white text-gray-700 dark:bg-slate-800 dark:text-slate-200',
      props.class,
    ]"
    type="button"
    @click="emit('click')"
  >
    {{ label }}
  </button>
</template>
