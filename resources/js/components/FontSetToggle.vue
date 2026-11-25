<script lang="ts" setup>
import { CaseSensitive } from '@lucide/vue';

defineProps<{
    active: boolean;
    label: string;
}>();

const emit = defineEmits<{
    (event: 'toggle'): void;
}>();
</script>

<template>
    <button
        :aria-label="label"
        :aria-pressed="active"
        class="inline-flex items-center justify-center rounded-sm border border-transparent p-2 text-primary hover:border-border"
        type="button"
        @click="emit('toggle')"
    >
        <CaseSensitive aria-hidden="true" class="h-5 w-5" />
    </button>
</template>