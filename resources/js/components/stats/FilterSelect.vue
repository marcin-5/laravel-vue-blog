<script lang="ts" setup>
interface Option {
    value: string | number;
    label: string;
}

interface Props {
    label: string;
    modelValue: string | number | null | undefined;
    options: Option[];
    placeholder?: string;
    minWidth?: string;
}

withDefaults(defineProps<Props>(), {
    placeholder: undefined,
    minWidth: 'auto',
});

defineEmits<{
    'update:modelValue': [value: string | number | null | undefined];
}>();
</script>

<template>
    <div class="flex flex-col">
        <label class="mb-1 text-xs text-muted-foreground">{{ label }}</label>
        <select
            :class="minWidth !== 'auto' ? minWidth : ''"
            :value="modelValue ?? ''"
            class="rounded-md border bg-background px-2 py-1 text-foreground"
            @change="
                $emit(
                    'update:modelValue',
                    ($event.target as HTMLSelectElement).value === ''
                        ? undefined
                        : Number(($event.target as HTMLSelectElement).value) || ($event.target as HTMLSelectElement).value,
                )
            "
        >
            <option v-if="placeholder" value="">{{ placeholder }}</option>
            <option v-for="opt in options" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
        </select>
    </div>
</template>
