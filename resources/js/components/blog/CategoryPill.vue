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
                ? 'border-mist-500 bg-mist-200 dark:border-olive-300 dark:bg-lime-900'
                : 'border-gray-300 bg-olive-50 text-gray-700 dark:bg-slate-800 dark:text-slate-200',
            props.class,
        ]"
        type="button"
        @click="emit('click')"
    >
        {{ label }}
    </button>
</template>
