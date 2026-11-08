<script lang="ts" setup>
import PostFormField from '@/components/blogger/PostFormField.vue';
import { Button } from '@/components/ui/button';
import { useTextareaRows } from '@/composables/useTextareaRows';

interface Props {
    id: string;
    storageKey: string;
    label: string;
    modelValue: string | null;
    error?: string;
    hint?: string;
    placeholder?: string;
    previewMode: boolean;
    previewLabel: string;
    closePreviewLabel: string;
    decreaseRowsLabel: string;
    increaseRowsLabel: string;
}

const props = defineProps<Props>();
const { rows, canDecrease, canIncrease, decrease, increase } = useTextareaRows(props.storageKey);
const emit = defineEmits<{
    input: [value: string];
    'update:modelValue': [value: string];
    togglePreview: [content: string];
}>();
</script>

<template>
    <PostFormField
        :id="props.id"
        :error="props.error"
        :hint="props.hint"
        :label="props.label"
        :model-value="props.modelValue"
        :placeholder="props.placeholder"
        :rows="rows"
        type="textarea"
        @input="(value: string) => emit('input', value)"
        @update:model-value="(value: string | null) => emit('update:modelValue', value ?? '')"
    >
        <template #header-actions>
            <div class="flex items-center gap-2">
                <Button :aria-label="props.decreaseRowsLabel" :disabled="!canDecrease" size="sm" type="button" variant="outline" @click="decrease">−</Button>
                <Button :aria-label="props.increaseRowsLabel" :disabled="!canIncrease" size="sm" type="button" variant="outline" @click="increase">+</Button>
                <Button :variant="props.previewMode ? 'exit' : 'toggle'" size="sm" type="button" @click="emit('togglePreview', props.modelValue ?? '')">
                    {{ props.previewMode ? props.closePreviewLabel : props.previewLabel }}
                </Button>
            </div>
        </template>
    </PostFormField>
</template>