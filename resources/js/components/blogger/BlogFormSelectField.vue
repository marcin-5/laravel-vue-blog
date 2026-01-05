<script lang="ts" setup>
import InputError from '@/components/InputError.vue';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

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

function handleUpdate(value: any) {
    emit('update:modelValue', value);
}
</script>

<template>
    <div class="flex items-center gap-2">
        <label :for="props.id" class="text-sm">{{ props.label }}</label>
        <Select :model-value="props.modelValue.toString()" @update:model-value="handleUpdate">
            <SelectTrigger :id="props.id" class="h-8 w-[180px]">
                <SelectValue placeholder="Wybierz opcję..." />
            </SelectTrigger>
            <SelectContent>
                <SelectItem v-for="option in props.options" :key="option.value" :value="option.value.toString()">
                    {{ option.label }}
                </SelectItem>
            </SelectContent>
        </Select>
        <InputError :message="props.error" />
    </div>
</template>
