<script lang="ts" setup>
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { AcceptableValue } from 'reka-ui';

interface Option {
    value: string | number;
    label: string;
}

interface Props {
    label: string;
    modelValue: any;
    options: Option[];
    placeholder?: string;
    minWidth?: string;
}

withDefaults(defineProps<Props>(), {
    placeholder: undefined,
    minWidth: 'auto',
});

defineEmits<{
    'update:modelValue': [value: AcceptableValue | undefined];
}>();
</script>

<template>
    <div class="flex flex-col">
        <label class="mb-1 text-xs text-muted-foreground">{{ label }}</label>
        <Select
            :model-value="modelValue?.toString() ?? ''"
            @update:model-value="$emit('update:modelValue', $event === '' ? undefined : Number($event) || $event)"
        >
            <SelectTrigger :class="minWidth !== 'auto' ? minWidth : ''" class="h-9">
                <SelectValue :placeholder="placeholder" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem v-for="opt in options" :key="opt.value" :value="opt.value.toString()">{{ opt.label }}</SelectItem>
            </SelectContent>
        </Select>
    </div>
</template>
