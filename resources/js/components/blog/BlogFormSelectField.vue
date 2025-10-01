<script lang="ts" setup>
import InputError from '@/components/InputError.vue';

interface SelectOption {
    value: string | number;
    label: string;
}

interface Props {
    id: string;
    label: string;
    modelValue: string | number;
    options: SelectOption[];
    error?: string;
}

interface Emits {
    (e: 'update:modelValue', value: string | number): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

function handleChange(event: Event) {
    const target = event.target as HTMLSelectElement;
    emit('update:modelValue', target.value);
}
</script>

<template>
    <div class="flex items-center gap-2">
        <label :for="props.id" class="text-sm">{{ props.label }}</label>
        <select
            :id="props.id"
            :value="props.modelValue"
            class="rounded border px-2 py-1 text-sm"
            @change="handleChange"
        >
            <option v-for="option in props.options" :key="option.value" :value="option.value">
                {{ option.label }}
            </option>
        </select>
        <InputError :message="props.error" />
    </div>
</template>