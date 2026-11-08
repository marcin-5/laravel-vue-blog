<script lang="ts" setup>
import type { MarkdownPreviewTranslations, PreviewLayout } from '@/types/blog.types';
import FullScreenPreview from '@/components/FullScreenPreview.vue';
import MarkdownEditorField from '@/components/blogger/MarkdownEditorField.vue';
import MarkdownPreview from '@/components/MarkdownPreview.vue';
import { Button } from '@/components/ui/button';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

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
const editorElement = ref<HTMLElement | null>(null);
const editorHeight = ref(0);
const previewHeightOffset = ref(0);
let resizeObserver: ResizeObserver | undefined;

const toggleLayoutLabel = computed(() =>
    props.previewLayout === 'vertical' ? props.translations.horizontalLayout : props.translations.verticalLayout,
);

const previewHeight = computed(() => {
    if (props.previewLayout === 'vertical' || editorHeight.value === 0) {
        return undefined;
    }

    return `${Math.max(120, editorHeight.value + previewHeightOffset.value)}px`;
});

watch(
    editorElement,
    (element) => {
        resizeObserver?.disconnect();
        if (!element || typeof ResizeObserver === 'undefined') {
            return;
        }

        resizeObserver = new ResizeObserver(([entry]) => {
            editorHeight.value = entry.contentRect.height;
        });
        resizeObserver.observe(element);
    },
    { flush: 'post' },
);

watch(
    () => props.isPreviewMode,
    (isPreviewMode) => {
        if (!isPreviewMode) {
            previewHeightOffset.value = 0;
        }
    },
);

onBeforeUnmount(() => resizeObserver?.disconnect());

function adjustPreviewHeight(amount: number) {
    previewHeightOffset.value += amount;
}

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
            <div :class="props.isPreviewMode && props.previewLayout === 'vertical' ? 'flex items-stretch gap-4' : 'space-y-4'">
                <!-- Markdown Editor -->
                <div ref="editorElement" :class="props.isPreviewMode && props.previewLayout === 'vertical' ? 'w-1/2' : ''">
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
                <div
                    v-if="props.isPreviewMode"
                    :class="props.previewLayout === 'vertical' ? 'flex w-1/2 flex-col' : 'flex flex-col'"
                    :style="{ height: previewHeight, minHeight: props.minHeight }"
                >
                    <div class="mb-1 flex items-end justify-between gap-2">
                        <h3 class="text-sm font-medium">{{ props.translations.previewLabel }}</h3>
                        <div class="flex gap-2">
                            <template v-if="props.previewLayout === 'horizontal'">
                                <Button :aria-label="props.translations.decreasePreviewHeight" size="sm" type="button" variant="outline" @click="adjustPreviewHeight(-24)">−</Button>
                                <Button :aria-label="props.translations.increasePreviewHeight" size="sm" type="button" variant="outline" @click="adjustPreviewHeight(24)">+</Button>
                            </template>
                            <Button size="sm" type="button" variant="exit" @click="emit('toggleFullPreview', props.modelValue ?? '')">
                                {{ props.translations.fullPreview }}
                            </Button>
                            <Button size="sm" type="button" variant="toggle" @click="handleLayoutToggle">
                                {{ toggleLayoutLabel }}
                            </Button>
                        </div>
                    </div>
                    <MarkdownPreview class="min-h-0 flex-1" :html="props.previewHtml" />
                </div>
            </div>
        </div>
    </div>
</template>
