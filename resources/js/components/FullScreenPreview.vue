<script lang="ts" setup>
import MarkdownPreview from '@/components/MarkdownPreview.vue';
import { Button } from '@/components/ui/button';
import { useAppearance } from '@/composables/useAppearance';
import { MoonIcon, SunIcon } from '@heroicons/vue/24/outline';
import { useMediaQuery } from '@vueuse/core';
import { computed } from 'vue';

interface Props {
    content: string;
    previewHtml: string;
    previewLayout: 'horizontal' | 'vertical';
    saveButtonLabel: string;
    createButtonLabel?: string;
    cancelButtonLabel: string;
    horizontalButtonLabel: string;
    verticalButtonLabel: string;
    exitPreviewButtonLabel: string;
    previewModeTitleLabel: string;
    markdownLabel: string;
    previewLabel: string;
    markdownPlaceholder?: string;
    isEdit: boolean;
    isProcessing?: boolean;
}

interface Emits {
    (e: 'update:content', value: string): void;
    (e: 'input', value: string): void;
    (e: 'save'): void;
    (e: 'cancel'): void;
    (e: 'exit'): void;
    (e: 'layout', value: 'horizontal' | 'vertical'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// Theme management
const { appearance, updateAppearance } = useAppearance();
const isSystemDark = useMediaQuery('(prefers-color-scheme: dark)');
const isDarkMode = computed(() => {
    if (appearance.value === 'system') {
        return isSystemDark.value;
    }
    return appearance.value === 'dark';
});

function toggleTheme() {
    updateAppearance(isDarkMode.value ? 'light' : 'dark');
}

function handleInput(event: Event) {
    const target = event.target as HTMLTextAreaElement;
    emit('update:content', target.value);
    emit('input', target.value);
}

function setLayoutHorizontal() {
    emit('layout', 'horizontal');
}

function setLayoutVertical() {
    emit('layout', 'vertical');
}

function handleExit() {
    emit('exit');
}

function handleSave() {
    emit('save');
}

function handleCancel() {
    emit('cancel');
}
</script>

<template>
    <div class="fixed inset-0 z-50 bg-background text-foreground">
        <div class="sticky top-0 z-10 flex items-center justify-between border-b border-border bg-background p-4">
            <h2 class="text-lg font-semibold">{{ props.previewModeTitleLabel }}</h2>
            <div class="flex gap-2">
                <Button :disabled="props.isProcessing" type="button" variant="constructive" @click="handleSave">
                    {{ props.isEdit ? props.saveButtonLabel : props.createButtonLabel || props.saveButtonLabel }}
                </Button>
                <Button type="button" variant="destructive" @click="handleCancel">{{ props.cancelButtonLabel }}</Button>
                <Button type="button" variant="toggle" @click="setLayoutHorizontal">
                    {{ props.horizontalButtonLabel }}
                </Button>
                <Button type="button" variant="toggle" @click="setLayoutVertical">
                    {{ props.verticalButtonLabel }}
                </Button>
                <Button class="flex items-center gap-2" type="button" variant="toggle" @click="toggleTheme">
                    <SunIcon v-if="isDarkMode" class="h-4 w-4" />
                    <MoonIcon v-else class="h-4 w-4" />
                </Button>
                <Button type="button" variant="exit" @click="handleExit">{{ props.exitPreviewButtonLabel }}</Button>
            </div>
        </div>
        <div class="h-full overflow-auto p-4">
            <div :class="props.previewLayout === 'vertical' ? 'flex h-full gap-4' : 'space-y-4'">
                <div :class="props.previewLayout === 'vertical' ? 'w-1/2' : ''">
                    <h3 class="mb-2 text-sm font-medium">{{ props.markdownLabel }}</h3>
                    <textarea
                        :placeholder="props.markdownPlaceholder"
                        :value="props.content"
                        class="h-96 w-full rounded border border-border bg-background px-3 py-2 font-mono text-sm text-foreground"
                        @input="handleInput"
                    />
                </div>
                <div :class="props.previewLayout === 'vertical' ? 'w-1/2' : ''">
                    <h3 class="mb-2 text-sm font-medium">{{ props.previewLabel }}</h3>
                    <MarkdownPreview :html="props.previewHtml" class="h-96" />
                </div>
            </div>
        </div>
    </div>
</template>
