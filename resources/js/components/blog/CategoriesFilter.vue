<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import type { CategoryItem } from '@/types/blog.types';
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import CategoryPill from './CategoryPill.vue';

interface Emits {
    (e: 'toggle', id: number): void;
    (e: 'clear'): void;
}

const props = defineProps<{
    categories: Pick<CategoryItem, 'id' | 'name'>[];
    selectedIds?: readonly number[];
    clearLabel?: string;
    class?: HTMLAttributes['class'];
}>();

const emit = defineEmits<Emits>();

const { t } = useI18n();

const selected = computed<readonly number[]>(() => props.selectedIds ?? []);
</script>

<template>
    <div :class="['flex flex-wrap items-center gap-2 font-nav', props.class]">
        <CategoryPill
            v-for="cat in categories"
            :key="cat.id"
            :label="typeof cat.name === 'string' ? cat.name : ''"
            :selected="selected.includes(cat.id)"
            @click="emit('toggle', cat.id)"
        />

        <Button
            v-if="selected.length > 0"
            class="ml-2 rounded-full border border-gray-500 bg-mist-50 px-3 py-1 text-sm text-gray-700 hover:bg-olive-100 dark:border-gray-600 dark:bg-slate-800 dark:text-slate-200 hover:dark:bg-slate-700"
            type="button"
            variant="ghost"
            @click="emit('clear')"
        >
            {{ clearLabel ?? t('actions.clear') }}
        </Button>
    </div>
</template>
