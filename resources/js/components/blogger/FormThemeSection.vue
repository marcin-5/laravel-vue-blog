<script lang="ts" setup>
import FormColorField from '@/components/blogger/FormColorField.vue';
import FormSelectField from '@/components/blogger/FormSelectField.vue';
import { Button } from '@/components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Slider } from '@/components/ui/slider';
import { TooltipButton } from '@/components/ui/tooltip';
import { useCssVariables } from '@/composables/useCssVariables';
import { FONT_OPTIONS, type ThemeTranslations, useThemeSection } from '@/composables/useThemeSection';
import { useToast } from '@/composables/useToast';
import type { BlogTheme, ThemeColors } from '@/types/blog.types';
import { ClipboardPaste, Copy, Download, Settings2, Upload } from 'lucide-vue-next';
import { useTemplateRef } from 'vue';

interface Props {
    title: string;
    idPrefix: string;
    colors?: ThemeColors;
    errors?: Record<string, any>;
    translations: ThemeTranslations;
}

const IMPORT_ERROR_FALLBACK = 'Import failed';
const IMPORT_SUCCESS_FALLBACK = 'Import successful';
const COPY_ERROR_FALLBACK = 'Clipboard copy failed';
const COPY_SUCCESS_FALLBACK = 'Theme copied to clipboard';
const PASTE_ERROR_FALLBACK = 'Clipboard content is invalid or unavailable';
const PASTE_SUCCESS_FALLBACK = 'Theme pasted successfully';
const DEFAULT_FONT_VALUE = 'inherit';
const THEME_FILE_EXTENSION = '.json';

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'update:colors', value: ThemeColors): void;
}>();

const { toast } = useToast();
const fileInput = useTemplateRef<HTMLInputElement>('fileInput');
const variantProbe = useTemplateRef<HTMLElement>('variantProbe');

const { variables } = useCssVariables([
    '--background',
    '--foreground',
    '--primary',
    '--primary-foreground',
    '--secondary',
    '--secondary-foreground',
    '--muted-foreground',
    '--border',
    '--link',
    '--link-hover',
    '--breadcrumb-link',
    '--breadcrumb-link-active',
    '--card',
], variantProbe);

const { updateValue, colorFields, fontFields } = useThemeSection(props, emit);

function resolveThemeVariant(): 'light' | 'dark' {
    return props.idPrefix.includes('light') ? 'light' : 'dark';
}

function isThemeColors(value: unknown): value is ThemeColors {
    return typeof value === 'object' && value !== null && !Array.isArray(value) && Object.values(value).every((color) => typeof color === 'string');
}

function isBlogTheme(value: unknown): value is BlogTheme {
    return (
        typeof value === 'object' &&
        value !== null &&
        !Array.isArray(value) &&
        ('light' in value || 'dark' in value) &&
        ((value as BlogTheme).light === undefined || isThemeColors((value as BlogTheme).light)) &&
        ((value as BlogTheme).dark === undefined || isThemeColors((value as BlogTheme).dark))
    );
}

function normalizeImportedTheme(theme: unknown): ThemeColors {
    if (isBlogTheme(theme)) {
        const variant = resolveThemeVariant();
        return theme[variant] ?? (variant === 'light' ? theme.dark : theme.light) ?? {};
    }

    if (isThemeColors(theme)) {
        return theme;
    }

    throw new Error('Invalid theme format');
}

function mergeColors(colors: ThemeColors): ThemeColors {
    return {
        ...(props.colors ?? {}),
        ...colors,
    };
}

function showImportErrorToast(): void {
    toast({
        title: props.translations.importError || IMPORT_ERROR_FALLBACK,
        variant: 'destructive',
    });
}

function showImportSuccessToast(): void {
    toast({
        title: props.translations.importSuccess || IMPORT_SUCCESS_FALLBACK,
        variant: 'default',
    });
}

function showCopyErrorToast(): void {
    toast({
        title: props.translations.copyError || COPY_ERROR_FALLBACK,
        variant: 'destructive',
    });
}

function showCopySuccessToast(): void {
    toast({
        title: props.translations.copySuccess || COPY_SUCCESS_FALLBACK,
        variant: 'default',
    });
}

function showPasteErrorToast(): void {
    toast({
        title: props.translations.pasteError || PASTE_ERROR_FALLBACK,
        variant: 'destructive',
    });
}

function showPasteSuccessToast(): void {
    toast({
        title: props.translations.pasteSuccess || PASTE_SUCCESS_FALLBACK,
        variant: 'default',
    });
}

function buildExportFileName(): string {
    const variant = resolveThemeVariant();
    const date = new Date().toISOString().split('T')[0];
    return `blog-theme-${variant}-${date}${THEME_FILE_EXTENSION}`;
}

function exportTheme(): void {
    const serializedTheme = JSON.stringify(props.colors ?? {}, null, 2);
    const blob = new Blob([serializedTheme], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');

    link.href = url;
    link.download = buildExportFileName();
    link.click();

    URL.revokeObjectURL(url);
}

async function copyTheme(): Promise<void> {
    try {
        await navigator.clipboard.writeText(JSON.stringify(props.colors ?? {}, null, 2));
        showCopySuccessToast();
    } catch {
        showCopyErrorToast();
    }
}

async function pasteTheme(): Promise<void> {
    try {
        const clipboardText = await navigator.clipboard.readText();
        const parsedTheme: unknown = JSON.parse(clipboardText);

        if (!isThemeColors(parsedTheme)) {
            showPasteErrorToast();
            return;
        }

        emit('update:colors', mergeColors(parsedTheme));
        showPasteSuccessToast();
    } catch {
        showPasteErrorToast();
    }
}

function openFilePicker(): void {
    fileInput.value?.click();
}

async function handleImport(event: Event): Promise<void> {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file) {
        return;
    }

    try {
        const fileContent = await file.text();
        const parsedTheme = JSON.parse(fileContent);

        const importedColors = normalizeImportedTheme(parsedTheme);
        emit('update:colors', mergeColors(importedColors));
        showImportSuccessToast();
    } catch {
        showImportErrorToast();
    } finally {
        input.value = '';
    }
}
</script>

<template>
    <div>
        <span ref="variantProbe" :class="resolveThemeVariant()" aria-hidden="true" class="hidden"></span>
        <div class="mb-2 flex items-center justify-between">
            <h4 class="text-sm font-medium opacity-80">{{ props.title }}</h4>
            <div class="flex gap-1">
                <input ref="fileInput" accept=".json" class="hidden" type="file" @change="handleImport" />
                <TooltipButton
                    :tooltip-content="props.translations.importTooltip || ''"
                    size="sm"
                    type="button"
                    variant="ghost"
                    @click="openFilePicker"
                >
                    <Upload class="h-3.5 w-3.5" />
                </TooltipButton>
                <TooltipButton :tooltip-content="props.translations.copyTooltip || ''" size="sm" type="button" variant="ghost" @click="copyTheme">
                    <Copy class="h-3.5 w-3.5" />
                </TooltipButton>
                <TooltipButton :tooltip-content="props.translations.pasteTooltip || ''" size="sm" type="button" variant="ghost" @click="pasteTheme">
                    <ClipboardPaste class="h-3.5 w-3.5" />
                </TooltipButton>
                <TooltipButton :tooltip-content="props.translations.exportTooltip || ''" size="sm" type="button" variant="ghost" @click="exportTheme">
                    <Download class="h-3.5 w-3.5" />
                </TooltipButton>
            </div>
        </div>

        <div class="mb-6 grid grid-cols-1 gap-4 border-b border-border pb-6 xl:grid-cols-2">
            <template v-for="field in fontFields" :key="`${props.idPrefix}-${field.idSuffix}`">
                <div class="flex w-full items-end gap-2">
                    <FormSelectField
                        :id="`${props.idPrefix}-${field.idSuffix}`"
                        :label="props.translations[field.labelKey] || field.defaultLabel"
                        :model-value="props.colors?.[field.key] || DEFAULT_FONT_VALUE"
                        :options="FONT_OPTIONS"
                        class="grow"
                        @update:model-value="updateValue(field.key, $event.toString())"
                    />
                    <Popover>
                        <PopoverTrigger as-child>
                            <Button class="h-8 w-8 shrink-0" size="icon" variant="ghost">
                                <Settings2 class="h-4 w-4" />
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-64">
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-medium">{{ props.translations.fontScaleCorrection || 'Size correction' }}</span>
                                    <span class="font-mono text-xs">{{ field.scaleValue.value[0] }}%</span>
                                </div>
                                <Slider
                                    :max="field.scaleMax"
                                    :min="field.scaleMin"
                                    :model-value="field.scaleValue.value"
                                    :step="1"
                                    @update:model-value="field.scaleValue.value = $event as number[]"
                                />
                            </div>
                        </PopoverContent>
                    </Popover>
                </div>

                <FormSelectField
                    v-if="field.additionalField"
                    :id="`${props.idPrefix}-${field.additionalField.idSuffix}`"
                    :label="props.translations[field.additionalField.labelKey] || field.additionalField.defaultLabel"
                    :model-value="props.colors?.[field.additionalField.key] || field.additionalField.defaultValue"
                    :options="field.additionalField.options"
                    class="w-full"
                    @update:model-value="updateValue(field.additionalField!.key, $event.toString())"
                />
            </template>
        </div>

        <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
            <FormColorField
                v-for="field in colorFields"
                :id="`${props.idPrefix}-${field.idSuffix}`"
                :key="field.key"
                :error="props.errors?.[field.key]"
                :label="props.translations[field.labelKey] || ''"
                :model-value="props.colors?.[field.key]"
                :placeholder="variables[field.key]"
                :tooltip="props.translations[field.tooltipKey]"
                @update:model-value="updateValue(field.key, $event)"
            />
        </div>
    </div>
</template>
