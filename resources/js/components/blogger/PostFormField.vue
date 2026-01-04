<script lang="ts" setup>
import InputError from '@/components/InputError.vue';
import { InfoIcon } from 'lucide-vue-next';

interface Props {
    id: string;
    label: string;
    modelValue: string | null;
    error?: string;
    placeholder?: string;
    type?: 'input' | 'textarea' | 'custom';
    rows?: number;
    required?: boolean;
    tooltip?: string;
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
        <label :for="props.id" class="mb-1 flex items-center gap-1 text-sm font-medium">
            {{ props.label }}
            <span v-if="props.tooltip" :title="props.tooltip" class="cursor-help text-muted-foreground">
                <InfoIcon :size="14" />
            </span>
        </label>
        <input
            v-if="props.type === 'input'"
            :id="props.id"
            :placeholder="props.placeholder"
            :required="props.required"
            :value="props.modelValue ?? ''"
            class="block w-full rounded-md border px-3 py-2"
            type="text"
            @input="handleInput"
        />
        <textarea
            v-else-if="props.type === 'textarea'"
            :id="props.id"
            :placeholder="props.placeholder"
            :rows="props.rows"
            :value="props.modelValue ?? ''"
            class="block w-full rounded-md border px-3 py-2"
            @input="handleInput"
        />
        <slot v-else />
        <InputError :message="props.error" />
    </div>
</template>
