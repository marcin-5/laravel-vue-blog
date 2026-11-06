<script lang="ts" setup>
import PostFormField from '@/components/blogger/PostFormField.vue';

interface Props {
    id: string;
    label: string;
    modelValue: string | null | undefined;
    error?: string;
    placeholder?: string;
    tooltip?: string;
}

interface Emits {
    (e: 'update:modelValue', value: string): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

function handleInput(event: Event) {
    const target = event.target as HTMLInputElement;
    emit('update:modelValue', target.value);
}
</script>

<template>
    <PostFormField :id="props.id" :error="props.error" :label="props.label" :tooltip="props.tooltip" type="custom">
        <div class="flex items-center gap-2">
            <input
                :id="props.id"
                :placeholder="props.placeholder"
                :value="props.modelValue ?? ''"
                class="w-full rounded border border-border bg-background p-2 text-foreground"
                type="text"
                @input="handleInput"
            />
            <span
                :style="{ backgroundColor: props.modelValue || props.placeholder || 'transparent' }"
                aria-hidden="true"
                class="h-9 w-9 shrink-0 rounded border border-border"
                data-testid="color-preview"
            ></span>
        </div>
    </PostFormField>
</template>
