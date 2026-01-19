<script lang="ts" setup>
import InputError from '@/components/InputError.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';

interface Props {
    id: string;
    label: string;
    modelValue: boolean;
    error?: string;
    additionalInfo?: string;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void;
}>();
</script>

<template>
    <div class="flex flex-col gap-1">
        <div class="flex items-center gap-2">
            <Checkbox :id="props.id" :checked="props.modelValue" @update:checked="(val) => emit('update:modelValue', val as boolean)" />
            <Label :for="props.id" class="cursor-pointer">
                {{ props.label }}
            </Label>
            <span v-if="props.additionalInfo" class="text-xs text-muted-foreground">
                {{ props.additionalInfo }}
            </span>
        </div>
        <InputError :message="props.error" />
    </div>
</template>
