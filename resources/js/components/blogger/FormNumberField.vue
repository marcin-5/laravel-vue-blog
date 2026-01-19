<script lang="ts" setup>
import InputError from '@/components/InputError.vue';

interface Props {
    id: string;
    label: string;
    modelValue: number;
    error?: string;
    min?: number;
    max?: number;
    step?: number;
    hint?: string;
}

interface Emits {
    (e: 'update:modelValue', value: number): void;
}

const props = withDefaults(defineProps<Props>(), {
    min: 1,
    max: 100,
    step: 1,
});

const emit = defineEmits<Emits>();

function handleInput(event: Event) {
    const target = event.target as HTMLInputElement;
    const value = parseInt(target.value, 10);
    emit('update:modelValue', value);
}
</script>

<template>
    <div>
        <label :for="props.id" class="mb-1 block text-sm font-medium">{{ props.label }}</label>
        <input
            :id="props.id"
            :max="props.max"
            :min="props.min"
            :step="props.step"
            :value="props.modelValue"
            class="block w-full rounded-md border px-3 py-2"
            type="number"
            @input="handleInput"
        />
        <p v-if="props.hint" class="mt-1 text-xs text-muted-foreground">{{ props.hint }}</p>
        <InputError :message="props.error" />
    </div>
</template>
