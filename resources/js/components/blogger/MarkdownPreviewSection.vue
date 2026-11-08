<script lang="ts" setup>
import type { MarkdownPreviewTranslations, PreviewLayout } from '@/types/blog.types';
import FullScreenPreview from '@/components/FullScreenPreview.vue';
import MarkdownEditorField from '@/components/blogger/MarkdownEditorField.vue';
import MarkdownPreview from '@/components/MarkdownPreview.vue';
import { Button } from '@/components/ui/button';
import { computed } from 'vue';

interface Props {
    id: string;
    storageKey: string;
    label: string;
    modelValue: string | null;
    error?: string;
    placeholder?: string;
    isPreviewMode: boolean;
    isFullPreview: boolean;
    previewLayout: PreviewLayout;
    previewHtml: string;
    minHeight?: string;
    isEdit?: boolean;
    isProcessing?: boolean;
    translations: MarkdownPreviewTranslations;
    showSaveButton?: boolean;
    hint?: string;
}

interface Emits {
    (e: 'update:modelValue', value: string): void;
    (e: 'input', value: string): void;
    (e: 'cancel'): void;
    (e: 'submit'): void;
    (e: 'togglePreview', content: string): void;
    (e: 'toggleFullPreview', content?: string): void;
    (e: 'setLayout', layout: PreviewLayout): void;
}

const props = withDefaults(defineProps<Props>(), {
    minHeight: '150px',
    isEdit: false,
    isProcessing: false,
    showSaveButton: true,
});

const emit = defineEmits<Emits>();

const toggleLayoutLabel = computed(() =>
    props.previewLayout === 'vertical' ? props.translations.horizontalLayout : props.translations.verticalLayout,
);

function handleLayoutToggle() {
    const newLayout = props.previewLayout === 'vertical' ? 'horizontal' : 'vertical';
    emit('setLayout', newLayout);
}
</script>

<template>
    <div>
        <!-- Full Preview Mode -->
        <FullScreenPreview
            v-if="props.isFullPreview"
            :cancel-button-label="props.translations.cancel"
            :content="props.modelValue ?? ''"
            :create-button-label="props.translations.create"
            :exit-preview-button-label="props.translations.exitPreview"
            :hide-save-button="!props.showSaveButton"
            :hint="props.hint"
            :is-edit="props.isEdit"
            :is-processing="props.isProcessing"
            :markdown-label="props.translations.markdownLabel"
            :markdown-placeholder="props.placeholder"
            :preview-html="props.previewHtml"
            :preview-label="props.translations.previewLabel"
            :preview-layout="props.previewLayout"
            :preview-mode-title-label="props.translations.previewModeTitle"
            :save-button-label="props.translations.save"
            :toggle-layout-button-label="toggleLayoutLabel"
            @cancel="emit('cancel')"
            @exit="emit('toggleFullPreview')"
            @input="(value: string) => emit('input', value)"
            @layout="(layout: PreviewLayout) => emit('setLayout', layout)"
            @save="emit('submit')"
            @update:content="(value: string) => emit('update:modelValue', value)"
        />

        <!-- Normal Mode -->
        <div v-else>
            <div :class="props.isPreviewMode && props.previewLayout === 'vertical' ? 'flex gap-4' : 'space-y-4'">
                <!-- Markdown Editor -->
                <div :class="props.isPreviewMode && props.previewLayout === 'vertical' ? 'w-1/2' : ''">
                    <MarkdownEditorField
                        :id="props.id"
                        :storage-key="props.storageKey"
                        :label="props.isPreviewMode ? props.translations.markdownLabel : props.label"
                        :model-value="props.modelValue"
                        :error="props.error"
                        :hint="props.hint"
                        :placeholder="props.placeholder"
                        :preview-mode="props.isPreviewMode"
                        :preview-label="props.translations.preview"
                        :close-preview-label="props.translations.closePreview"
                        :decrease-rows-label="props.translations.decreaseRows"
                        :increase-rows-label="props.translations.increaseRows"
                        @input="(value: string) => emit('input', value)"
                        @update:model-value="(value: string) => emit('update:modelValue', value)"
                        @toggle-preview="emit('togglePreview', $event)"
                    />
                </div>
                <!-- Preview Pane -->
                <div v-if="props.isPreviewMode" :class="props.previewLayout === 'vertical' ? 'w-1/2' : ''">
                    <h3 class="mb-1 text-sm font-medium">{{ props.translations.previewLabel }}</h3>
                    <MarkdownPreview :class="`min-h-[${props.minHeight}]`" :html="props.previewHtml" />
                </div>
            </div>
        </div>

        <div v-if="props.isPreviewMode" class="mt-2 flex justify-end gap-2">
            <Button v-if="props.isPreviewMode" size="sm" type="button" variant="exit" @click="emit('toggleFullPreview', props.modelValue ?? '')">
                {{ props.isFullPreview ? props.translations.splitView : props.translations.fullPreview }}
            </Button>
            <Button v-if="props.isPreviewMode && !props.isFullPreview" size="sm" type="button" variant="toggle" @click="handleLayoutToggle">
                {{ toggleLayoutLabel }}
            </Button>
        </div>
    </div>
</template>
