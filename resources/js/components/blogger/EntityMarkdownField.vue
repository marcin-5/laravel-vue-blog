<script lang="ts" setup>
import { useMarkdownPreviewSection } from '@/composables/useMarkdownPreviewSection';
import { computed } from 'vue';
import MarkdownPreviewSection from './MarkdownPreviewSection.vue';

interface Props {
    id: string;
    modelValue: string | null;
    error?: string;
    label: string;
    placeholder?: string;
    isEdit?: boolean;
    isProcessing?: boolean;
    translations: any; // Result of createMarkdownTranslations
}

const props = defineProps<Props>();
const emit = defineEmits<{
    (e: 'update:modelValue', value: string | null): void;
    (e: 'cancel'): void;
}>();

const preview = useMarkdownPreviewSection();

const value = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val),
});

function handleTogglePreview() {
    preview.togglePreview(props.modelValue || '');
}

function handleToggleFullPreview() {
    preview.toggleFullPreview(props.modelValue || '');
}

function handleCancel() {
    emit('cancel');
}
</script>

<template>
    <MarkdownPreviewSection
        :id="props.id"
        v-model="value"
        :error="props.error"
        :is-edit="props.isEdit"
        :is-full-preview="preview.isFullPreview.value"
        :is-preview-mode="preview.isPreviewMode.value"
        :is-processing="props.isProcessing"
        :label="props.label"
        :placeholder="props.placeholder"
        :preview-html="preview.previewHtml.value"
        :preview-layout="preview.previewLayout.value"
        :show-save-button="false"
        :translations="props.translations"
        @cancel="handleCancel"
        @input="preview.handleInput(props.modelValue || '')"
        @set-layout="preview.setLayout"
        @toggle-full-preview="handleToggleFullPreview"
        @toggle-preview="handleTogglePreview"
    />
</template>
