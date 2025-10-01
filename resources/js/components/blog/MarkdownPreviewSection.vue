<script lang="ts" setup>
import FullScreenPreview from '@/components/FullScreenPreview.vue';
import MarkdownPreview from '@/components/MarkdownPreview.vue';
import { Button } from '@/components/ui/button';
import InputError from '@/components/InputError.vue';

interface TranslationKeys {
    cancel: string;
    create: string;
    save: string;
    exitPreview: string;
    markdownLabel: string;
    previewLabel: string;
    previewModeTitle: string;
    horizontal: string;
    vertical: string;
    closePreview: string;
    preview: string;
    fullPreview: string;
    splitView: string;
}

interface Props {
    id: string;
    label: string;
    modelValue: string;
    error?: string;
    placeholder?: string;
    isPreviewMode: boolean;
    isFullPreview: boolean;
    previewLayout: 'horizontal' | 'vertical';
    previewHtml: string;
    rows?: number;
    minHeight?: string;
    isEdit?: boolean;
    isProcessing?: boolean;
    translations: TranslationKeys;
}

interface Emits {
    (e: 'update:modelValue', value: string): void;
    (e: 'input'): void;
    (e: 'cancel'): void;
    (e: 'submit'): void;
    (e: 'togglePreview'): void;
    (e: 'toggleFullPreview'): void;
    (e: 'setLayoutHorizontal'): void;
    (e: 'setLayoutVertical'): void;
}

const props = withDefaults(defineProps<Props>(), {
    rows: 3,
    minHeight: '150px',
    isEdit: false,
    isProcessing: false,
});

const emit = defineEmits<Emits>();

function handleInput(event: Event) {
    const target = event.target as HTMLTextAreaElement;
    emit('update:modelValue', target.value);
    emit('input');
}

function handleLayoutChange(layout: string) {
    if (layout === 'horizontal') {
        emit('setLayoutHorizontal');
    } else {
        emit('setLayoutVertical');
    }
}
</script>

<template>
    <div>
        <div class="mb-1">
            <label :for="props.id" class="block text-sm font-medium">{{ props.label }}</label>
        </div>

        <!-- Full Preview Mode -->
        <FullScreenPreview
            v-if="props.isFullPreview"
            :content="props.modelValue"
            @update:content="(value: string) => emit('update:modelValue', value)"
            :cancel-button-label="props.translations.cancel"
            :create-button-label="props.translations.create"
            :exit-preview-button-label="props.translations.exitPreview"
            :horizontal-button-label="props.translations.horizontal"
            :is-edit="props.isEdit"
            :is-processing="props.isProcessing"
            :markdown-label="props.translations.markdownLabel"
            :markdown-placeholder="props.placeholder"
            :preview-html="props.previewHtml"
            :preview-label="props.translations.previewLabel"
            :preview-layout="props.previewLayout"
            :preview-mode-title-label="props.translations.previewModeTitle"
            :save-button-label="props.translations.save"
            :vertical-button-label="props.translations.vertical"
            @cancel="emit('cancel')"
            @exit="emit('toggleFullPreview')"
            @input="emit('input')"
            @layout="handleLayoutChange"
            @save="emit('submit')"
        />

        <!-- Normal Mode -->
        <div v-else>
            <div :class="props.isPreviewMode && props.previewLayout === 'vertical' ? 'flex gap-4' : 'space-y-4'">
                <!-- Markdown Editor -->
                <div :class="props.isPreviewMode && props.previewLayout === 'vertical' ? 'w-1/2' : ''">
                    <textarea
                        :id="props.id"
                        :placeholder="props.placeholder"
                        :rows="props.isPreviewMode ? (props.isEdit ? 6 : 8) : props.rows"
                        :value="props.modelValue"
                        class="block w-full rounded-md border px-3 py-2"
                        @input="handleInput"
                    />
                </div>
                <!-- Preview Pane -->
                <div v-if="props.isPreviewMode" :class="props.previewLayout === 'vertical' ? 'w-1/2' : ''">
                    <MarkdownPreview :html="props.previewHtml" :class="`min-h-[${props.minHeight}]`" />
                </div>
            </div>
        </div>

        <InputError :message="props.error" />

        <!-- Preview Controls -->
        <div class="mt-2 flex justify-end gap-2">
            <Button
                :variant="props.isPreviewMode ? 'exit' : 'toggle'"
                size="sm"
                type="button"
                @click="emit('togglePreview')"
            >
                {{ props.isPreviewMode ? props.translations.closePreview : props.translations.preview }}
            </Button>
            <Button
                v-if="props.isPreviewMode"
                size="sm"
                type="button"
                variant="exit"
                @click="emit('toggleFullPreview')"
            >
                {{ props.isFullPreview ? props.translations.splitView : props.translations.fullPreview }}
            </Button>
            <Button
                v-if="props.isPreviewMode && !props.isFullPreview"
                size="sm"
                type="button"
                variant="toggle"
                @click="emit('setLayoutHorizontal')"
            >
                {{ props.translations.horizontal }}
            </Button>
            <Button
                v-if="props.isPreviewMode && !props.isFullPreview"
                size="sm"
                type="button"
                variant="toggle"
                @click="emit('setLayoutVertical')"
            >
                {{ props.translations.vertical }}
            </Button>
        </div>
    </div>
</template>