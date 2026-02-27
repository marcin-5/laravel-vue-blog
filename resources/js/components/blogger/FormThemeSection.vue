<script lang="ts" setup>
import FormColorField from '@/components/blogger/FormColorField.vue';
import FormSelectField from '@/components/blogger/FormSelectField.vue';
import { Button } from '@/components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Slider } from '@/components/ui/slider';
import { TooltipButton } from '@/components/ui/tooltip';
import { useCssVariables } from '@/composables/useCssVariables';
import { useToast } from '@/composables/useToast';
import type { ThemeColors } from '@/types/blog.types';
import { Download, Settings2, Upload } from 'lucide-vue-next';
import { computed, useTemplateRef } from 'vue';

interface Props {
    title: string;
    idPrefix: string;
    colors?: ThemeColors;
    errors?: Record<string, any>;
    translations: {
        background: string;
        backgroundTooltip: string;
        foreground: string;
        foregroundTooltip: string;
        primary: string;
        primaryTooltip: string;
        primaryForeground: string;
        primaryForegroundTooltip: string;
        secondary: string;
        secondaryTooltip: string;
        secondaryForeground: string;
        secondaryForegroundTooltip: string;
        mutedForeground: string;
        mutedForegroundTooltip: string;
        border: string;
        borderTooltip: string;
        link: string;
        linkTooltip: string;
        linkHover: string;
        linkHoverTooltip: string;
        breadcrumbLink: string;
        breadcrumbLinkTooltip: string;
        breadcrumbLinkActive: string;
        breadcrumbLinkActiveTooltip: string;
        card: string;
        cardTooltip: string;
        fontHeader?: string;
        fontBody?: string;
        fontMotto?: string;
        fontExcerpt?: string;
        fontFooter?: string;
        fontScaleCorrection?: string;
        mottoStyle?: string;
        footerScale?: string;
        exportTooltip?: string;
        importTooltip?: string;
        importError?: string;
        importSuccess?: string;
    };
}

const props = defineProps<Props>();

const { toast } = useToast();
const fileInput = useTemplateRef<HTMLInputElement>('fileInput');

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
]);

// Define the update event (following v-model convention for the colors object)
const emit = defineEmits<{
    (e: 'update:colors', value: ThemeColors): void;
}>();

function exportTheme() {
    const data = JSON.stringify(props.colors ?? {}, null, 2);
    const blob = new Blob([data], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    const variant = props.idPrefix.includes('light') ? 'light' : 'dark';
    link.download = `blog-theme-${variant}-${new Date().toISOString().split('T')[0]}.json`;
    link.click();
    URL.revokeObjectURL(url);
}

function triggerImport() {
    fileInput.value?.click();
}

async function handleImport(event: Event) {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) {
        return;
    }

    try {
        const text = await file.text();
        const theme = JSON.parse(text);

        // Basic validation
        if (typeof theme !== 'object' || theme === null) {
            toast({
                title: props.translations.importError || 'Import failed',
                variant: 'destructive',
            });
            return;
        }

        let colorsToImport = theme;

        // Compatibility check: if the user tries to import a full theme (with light/dark keys)
        // into a specific section, we should pick the relevant part or the whole object if it's flat.
        if (theme.light || theme.dark) {
            const variant = props.idPrefix.includes('light') ? 'light' : 'dark';
            colorsToImport = theme[variant] || (variant === 'light' ? theme.dark : theme.light) || theme;
        }

        emit('update:colors', {
            ...(props.colors ?? {}),
            ...colorsToImport,
        });
        toast({
            title: props.translations.importSuccess || 'Import successful',
            variant: 'default',
        });
    } catch {
        toast({
            title: props.translations.importError || 'Import failed',
            variant: 'destructive',
        });
    } finally {
        target.value = '';
    }
}

// Helper function to update a specific key in an object
function updateValue(key: string, value: string) {
    const updatedColors = {
        ...(props.colors ?? {}),
        [key]: value,
    };

    emit('update:colors', updatedColors);
}

const fontOptions = [
    { label: 'System Default', value: 'inherit' },
    { label: '[Sn] Afacad', value: 'var(--font-afacad)' },
    { label: '[Sn] Darker Grotesque', value: 'var(--font-darker-grotesque)' },
    { label: '[Sn] DM Sans', value: 'var(--font-dm-sans)' },
    { label: '[Sn] Inter', value: 'var(--font-inter)' },
    { label: '[Sn] Montserrat', value: 'var(--font-montserrat)' },
    { label: '[Sn] Nunito', value: 'var(--font-nunito)' },
    { label: '[Sn] Quicksand', value: 'var(--font-quicksand)' },
    { label: '[Sn] Raleway', value: 'var(--font-raleway)' },
    { label: '[Sn] Recursive', value: 'var(--font-recursive)' },
    { label: '[Sn] Roboto', value: 'var(--font-roboto)' },
    { label: '[Sn] Sofia semi condensed', value: 'var(--font-sofia-semi-condensed)' },
    { label: '[Rm] Bitter', value: 'var(--font-bitter)' },
    { label: '[Rm] Faustina', value: 'var(--font-faustina)' },
    { label: '[Rm] Literata', value: 'var(--font-literata)' },
    { label: '[Rm] Kreon', value: 'var(--font-kreon)' },
    { label: '[Rm] Rokkitt', value: 'var(--font-rokkitt)' },
    { label: '[Rm] Vollkorn', value: 'var(--font-vollkorn)' },
    { label: '[Rm] Yrsa', value: 'var(--font-yrsa)' },
];

const mottoStyleOptions = [
    { label: 'Italic', value: 'italic' },
    { label: 'Normal', value: 'normal' },
];

function getBaseScale(scaleKey: string): number {
    return parseFloat(props.colors?.[scaleKey] || '1');
}

function updateBaseScale(scaleKey: string, newBaseScale: number) {
    updateValue(scaleKey, newBaseScale.toString());
}

const headerScaleValue = computed({
    get: () => [Math.round(getBaseScale('--header-scale') * 100)],
    set: (val) => updateBaseScale('--header-scale', val[0] / 100),
});

const bodyScaleValue = computed({
    get: () => [Math.round(getBaseScale('--body-scale') * 100)],
    set: (val) => updateBaseScale('--body-scale', val[0] / 100),
});

const mottoScaleValue = computed({
    get: () => [Math.round(getBaseScale('--motto-scale') * 100)],
    set: (val) => updateBaseScale('--motto-scale', val[0] / 100),
});

const excerptScaleValue = computed({
    get: () => [Math.round(getBaseScale('--excerpt-scale') * 100)],
    set: (val) => updateBaseScale('--excerpt-scale', val[0] / 100),
});

const footerScaleValue = computed({
    get: () => [Math.round(getBaseScale('--footer-scale') * 100)],
    set: (val) => updateBaseScale('--footer-scale', val[0] / 100),
});

const colorFields = computed(() => [
    { key: '--primary', idSuffix: 'primary', labelKey: 'primary' as const, tooltipKey: 'primaryTooltip' as const },
    { key: '--background', idSuffix: 'background', labelKey: 'background' as const, tooltipKey: 'backgroundTooltip' as const },
    { key: '--secondary', idSuffix: 'secondary', labelKey: 'secondary' as const, tooltipKey: 'secondaryTooltip' as const },
    { key: '--foreground', idSuffix: 'foreground', labelKey: 'foreground' as const, tooltipKey: 'foregroundTooltip' as const },
    { key: '--primary-foreground', idSuffix: 'primary-fg', labelKey: 'primaryForeground' as const, tooltipKey: 'primaryForegroundTooltip' as const },
    { key: '--muted-foreground', idSuffix: 'muted-fg', labelKey: 'mutedForeground' as const, tooltipKey: 'mutedForegroundTooltip' as const },
    {
        key: '--secondary-foreground',
        idSuffix: 'secondary-fg',
        labelKey: 'secondaryForeground' as const,
        tooltipKey: 'secondaryForegroundTooltip' as const,
    },
    { key: '--border', idSuffix: 'border', labelKey: 'border' as const, tooltipKey: 'borderTooltip' as const },
    { key: '--link', idSuffix: 'link', labelKey: 'link' as const, tooltipKey: 'linkTooltip' as const },
    { key: '--breadcrumb-link', idSuffix: 'breadcrumb-link', labelKey: 'breadcrumbLink' as const, tooltipKey: 'breadcrumbLinkTooltip' as const },
    { key: '--link-hover', idSuffix: 'link-hover', labelKey: 'linkHover' as const, tooltipKey: 'linkHoverTooltip' as const },
    {
        key: '--breadcrumb-link-active',
        idSuffix: 'breadcrumb-link-active',
        labelKey: 'breadcrumbLinkActive' as const,
        tooltipKey: 'breadcrumbLinkActiveTooltip' as const,
    },
    { key: '--card', idSuffix: 'card', labelKey: 'card' as const, tooltipKey: 'card' as const },
]);

const fontFields = computed(() => [
    {
        key: '--font-header',
        idSuffix: 'font-header',
        labelKey: 'fontHeader' as const,
        defaultLabel: 'Font nagłówków',
        scaleKey: '--header-scale',
        scaleValue: headerScaleValue,
        scaleMin: 90,
        scaleMax: 120,
    },
    {
        key: '--font-body',
        idSuffix: 'font-body',
        labelKey: 'fontBody' as const,
        defaultLabel: 'Font treści',
        scaleKey: '--body-scale',
        scaleValue: bodyScaleValue,
        scaleMin: 90,
        scaleMax: 120,
    },
    {
        key: '--font-motto',
        idSuffix: 'font-motto',
        labelKey: 'fontMotto' as const,
        defaultLabel: 'Font motto/cytatów',
        scaleKey: '--motto-scale',
        scaleValue: mottoScaleValue,
        scaleMin: 90,
        scaleMax: 120,
        additionalField: {
            key: '--motto-style',
            idSuffix: 'motto-style',
            labelKey: 'mottoStyle' as const,
            defaultLabel: 'Styl motto/cytatów',
            options: mottoStyleOptions,
            defaultValue: 'italic',
        },
    },
    {
        key: '--font-excerpt',
        idSuffix: 'font-excerpt',
        labelKey: 'fontExcerpt' as const,
        defaultLabel: 'Font tekstu zachęty',
        scaleKey: '--excerpt-scale',
        scaleValue: excerptScaleValue,
        scaleMin: 90,
        scaleMax: 120,
    },
    {
        key: '--font-footer',
        idSuffix: 'font-footer',
        labelKey: 'fontFooter' as const,
        defaultLabel: 'Font stopki',
        scaleKey: '--footer-scale',
        scaleValue: footerScaleValue,
        scaleMin: 80,
        scaleMax: 110,
    },
]);
</script>

<template>
    <div>
        <div class="mb-2 flex items-center justify-between">
            <h4 class="text-sm font-medium opacity-80">{{ props.title }}</h4>
            <div class="flex gap-1">
                <input ref="fileInput" accept=".json" class="hidden" type="file" @change="handleImport" />
                <TooltipButton
                    :tooltip-content="props.translations.importTooltip || ''"
                    size="sm"
                    type="button"
                    variant="ghost"
                    @click="triggerImport"
                >
                    <Upload class="h-3.5 w-3.5" />
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
                        :model-value="props.colors?.[field.key] || 'inherit'"
                        :options="fontOptions"
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
                :label="props.translations[field.labelKey]"
                :model-value="props.colors?.[field.key]"
                :placeholder="variables[field.key]"
                :tooltip="props.translations[field.tooltipKey]"
                @update:model-value="updateValue(field.key, $event)"
            />
        </div>
    </div>
</template>
