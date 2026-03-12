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
                ? 'border-mist-500 bg-mist-200 text-mist-900 dark:border-gray-500 dark:bg-mist-700 dark:text-slate-200'
                : 'border-gray-300 bg-mist-50 text-gray-700 dark:border-gray-600 dark:bg-slate-800 dark:text-slate-200',
            props.class,
        ]"
        type="button"
        @click="emit('click')"
    >
        {{ label }}
    </button>
</template>
