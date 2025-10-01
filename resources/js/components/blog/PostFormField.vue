<script lang="ts" setup>
import InputError from '@/components/InputError.vue';

interface Props {
    id: string;
    label: string;
    modelValue: string;
    error?: string;
    placeholder?: string;
    type?: 'input' | 'textarea';
    rows?: number;
    required?: boolean;
}

interface Emits {
    (e: 'update:modelValue', value: string): void;
    (e: 'input'): void;
}

const props = withDefaults(defineProps<Props>(), {
    type: 'input',
    rows: 2,
    required: false,
});

const emit = defineEmits<Emits>();

function handleInput(event: Event) {
    const target = event.target as HTMLInputElement | HTMLTextAreaElement;
    emit('update:modelValue', target.value);
    emit('input');
}
</script>

<template>
    <div>
        <label :for="props.id" class="mb-1 block text-sm font-medium">{{ props.label }}</label>
        <input
            v-if="props.type === 'input'"
            :id="props.id"
            :placeholder="props.placeholder"
            :required="props.required"
            :value="props.modelValue"
            class="block w-full rounded-md border px-3 py-2"
            type="text"
            @input="handleInput"
        />
        <textarea
            v-else
            :id="props.id"
            :placeholder="props.placeholder"
            :rows="props.rows"
            :value="props.modelValue"
            class="block w-full rounded-md border px-3 py-2"
            @input="handleInput"
        />
        <InputError :message="props.error" />
    </div>
</template>
