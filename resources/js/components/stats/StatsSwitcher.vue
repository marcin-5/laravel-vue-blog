<script lang="ts" setup>
import { Button } from '@/components/ui/button';

defineProps<{
    options: { value: string; label: string }[];
    modelValue: string;
}>();

defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();
</script>

<template>
    <div class="flex gap-1">
        <Button
            v-for="option in options"
            :key="option.value"
            :class="['h-7 w-auto px-2 text-xs', { 'bg-accent': modelValue === option.value }]"
            size="icon"
            variant="ghost"
            @click="$emit('update:modelValue', option.value)"
        >
            {{ option.label }}
        </Button>
    </div>
</template>
