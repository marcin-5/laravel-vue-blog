<script lang="ts" setup>
import { computed } from 'vue';
import CategoryPill from './CategoryPill.vue';
import type { Category } from '@/types';
import { useI18nGate } from '@/composables/useI18nGate';

interface Emits {
  (e: 'toggle', id: number): void;
  (e: 'clear'): void;
}

const props = defineProps<{
  categories: Pick<Category, 'id' | 'name'>[];
  selectedIds?: number[];
  clearLabel?: string;
  class?: string;
}>();

const emit = defineEmits<Emits>();

const { ready: i18nReady, t } = await useI18nGate('landing');

const selected = computed<number[]>(() => props.selectedIds ?? []);
</script>

<template>
  <div :class="['flex flex-wrap items-center gap-2', props.class]">
    <CategoryPill
      v-for="cat in categories"
      :key="cat.id"
      :label="typeof cat.name === 'string' ? cat.name : ''"
      :selected="selected.includes(cat.id)"
      @click="emit('toggle', cat.id)"
    />

    <button
      v-if="selected.length > 0 && i18nReady"
      class="ml-2 rounded-full border border-gray-300 px-3 py-1 text-sm text-gray-700 hover:bg-gray-50 dark:bg-slate-800 dark:text-slate-200"
      type="button"
      @click="emit('clear')"
    >
      {{ clearLabel ?? t('landing.actions.clear') }}
    </button>
  </div>
</template>
