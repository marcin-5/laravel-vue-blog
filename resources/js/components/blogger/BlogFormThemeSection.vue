<script lang="ts" setup>
import BlogFormColorField from '@/components/blogger/BlogFormColorField.vue';
import BlogFormSelectField from '@/components/blogger/BlogFormSelectField.vue';
import { Button } from '@/components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Slider } from '@/components/ui/slider';
import { useCssVariables } from '@/composables/useCssVariables';
import type { ThemeColors } from '@/types/blog.types';
import { Settings2 } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    title: string;
    idPrefix: string;
    colors: ThemeColors;
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
        fontHeader?: string;
        fontBody?: string;
        fontMotto?: string;
        fontFooter?: string;
        mottoStyle?: string;
        footerScale?: string;
    };
}

const props = defineProps<Props>();

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
]);

// Define the update event (following v-model convention for the colors object)
const emit = defineEmits<{
    (e: 'update:colors', value: ThemeColors): void;
}>();

const fontCorrections: Record<string, number> = {
    'var(--font-yrsa)': 1.1,
    'var(--font-bitter)': 1.0,
    'var(--font-nunito)': 1.0,
    'var(--font-dm-sans)': 1.0,
    'var(--font-inter)': 1.0,
    'var(--font-montserrat)': 1.0,
    'var(--font-quicksand)': 1.0,
    'var(--font-raleway)': 1.0,
    'var(--font-recursive)': 1.0,
    'var(--font-roboto)': 1.0,
    'var(--font-faustina)': 1.0,
    'var(--font-literata)': 1.0,
    'var(--font-kreon)': 1.0,
    'var(--font-rokkitt)': 1.0,
    'var(--font-vollkorn)': 1.0,
};

// Helper function to update a specific key in an object
function updateValue(key: string, value: string) {
    const updatedColors = {
        ...props.colors,
        [key]: value,
    };

    // Auto-correction logic for font scales
    if (key === '--font-header') {
        const correction = fontCorrections[value] || 1.0;
        updatedColors['--header-scale'] = correction.toString();
    } else if (key === '--font-body') {
        const correction = fontCorrections[value] || 1.0;
        updatedColors['--body-scale'] = correction.toString();
    } else if (key === '--font-motto') {
        const correction = fontCorrections[value] || 1.0;
        updatedColors['--motto-scale'] = correction.toString();
    } else if (key === '--font-footer') {
        const correction = fontCorrections[value] || 1.0;
        updatedColors['--footer-scale'] = correction.toString();
    }

    emit('update:colors', updatedColors);
}

const fontOptions = [
    { label: 'System Default', value: 'inherit' },
    { label: 'DM Sans', value: 'var(--font-dm-sans)' },
    { label: 'Inter', value: 'var(--font-inter)' },
    { label: 'Montserrat', value: 'var(--font-montserrat)' },
    { label: 'Nunito', value: 'var(--font-nunito)' },
    { label: 'Quicksand', value: 'var(--font-quicksand)' },
    { label: 'Raleway', value: 'var(--font-raleway)' },
    { label: 'Recursive', value: 'var(--font-recursive)' },
    { label: 'Roboto', value: 'var(--font-roboto)' },
    { label: 'Bitter', value: 'var(--font-bitter)' },
    { label: 'Faustina', value: 'var(--font-faustina)' },
    { label: 'Literata', value: 'var(--font-literata)' },
    { label: 'Kreon', value: 'var(--font-kreon)' },
    { label: 'Rokkitt', value: 'var(--font-rokkitt)' },
    { label: 'Vollkorn', value: 'var(--font-vollkorn)' },
    { label: 'Yrsa', value: 'var(--font-yrsa)' },
];

const mottoStyleOptions = [
    { label: 'Italic', value: 'italic' },
    { label: 'Normal', value: 'normal' },
];

const headerScaleValue = computed({
    get: () => [Math.round(parseFloat(props.colors['--header-scale'] || '1') * 100)],
    set: (val) => updateValue('--header-scale', (val[0] / 100).toString()),
});

const bodyScaleValue = computed({
    get: () => [Math.round(parseFloat(props.colors['--body-scale'] || '1') * 100)],
    set: (val) => updateValue('--body-scale', (val[0] / 100).toString()),
});

const mottoScaleValue = computed({
    get: () => [Math.round(parseFloat(props.colors['--motto-scale'] || '1') * 100)],
    set: (val) => updateValue('--motto-scale', (val[0] / 100).toString()),
});

const footerScaleValue = computed({
    get: () => [Math.round(parseFloat(props.colors['--footer-scale'] || '1') * 100)],
    set: (val) => updateValue('--footer-scale', (val[0] / 100).toString()),
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
]);
</script>

<template>
    <div>
        <h4 class="mb-2 text-sm font-medium opacity-80">{{ props.title }}</h4>

        <div class="mb-6 grid grid-cols-1 gap-4 border-b border-border pb-6 sm:grid-cols-2">
            <!-- Header Font -->
            <div class="flex items-end gap-2">
                <BlogFormSelectField
                    :id="`${props.idPrefix}-font-header`"
                    :label="props.translations.fontHeader || 'Font nagłówków'"
                    :model-value="props.colors['--font-header'] || 'inherit'"
                    :options="fontOptions"
                    class="grow"
                    @update:model-value="updateValue('--font-header', $event.toString())"
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
                                <span class="text-xs font-medium">Korekcja wielkości</span>
                                <span class="font-mono text-xs">{{ headerScaleValue[0] }}%</span>
                            </div>
                            <Slider
                                :max="120"
                                :min="90"
                                :model-value="headerScaleValue"
                                :step="1"
                                @update:model-value="headerScaleValue = $event as number[]"
                            />
                        </div>
                    </PopoverContent>
                </Popover>
            </div>

            <!-- Body Font -->
            <div class="flex items-end gap-2">
                <BlogFormSelectField
                    :id="`${props.idPrefix}-font-body`"
                    :label="props.translations.fontBody || 'Font treści'"
                    :model-value="props.colors['--font-body'] || 'inherit'"
                    :options="fontOptions"
                    class="grow"
                    @update:model-value="updateValue('--font-body', $event.toString())"
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
                                <span class="text-xs font-medium">Korekcja wielkości</span>
                                <span class="font-mono text-xs">{{ bodyScaleValue[0] }}%</span>
                            </div>
                            <Slider
                                :max="120"
                                :min="90"
                                :model-value="bodyScaleValue"
                                :step="1"
                                @update:model-value="bodyScaleValue = $event as number[]"
                            />
                        </div>
                    </PopoverContent>
                </Popover>
            </div>

            <!-- Motto Font & Style -->
            <div class="flex flex-col gap-2">
                <div class="flex items-end gap-2">
                    <BlogFormSelectField
                        :id="`${props.idPrefix}-font-motto`"
                        :label="props.translations.fontMotto || 'Font motto/cytatów'"
                        :model-value="props.colors['--font-motto'] || 'inherit'"
                        :options="fontOptions"
                        class="grow"
                        @update:model-value="updateValue('--font-motto', $event.toString())"
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
                                    <span class="text-xs font-medium">Korekcja wielkości</span>
                                    <span class="font-mono text-xs">{{ mottoScaleValue[0] }}%</span>
                                </div>
                                <Slider
                                    :max="120"
                                    :min="90"
                                    :model-value="mottoScaleValue"
                                    :step="1"
                                    @update:model-value="mottoScaleValue = $event as number[]"
                                />
                            </div>
                        </PopoverContent>
                    </Popover>
                </div>
                <BlogFormSelectField
                    :id="`${props.idPrefix}-motto-style`"
                    :label="props.translations.mottoStyle || 'Styl motto/cytatów'"
                    :model-value="props.colors['--motto-style'] || 'italic'"
                    :options="mottoStyleOptions"
                    @update:model-value="updateValue('--motto-style', $event.toString())"
                />
            </div>

            <!-- Footer Font & Scale -->
            <div class="flex flex-col gap-2">
                <div class="flex items-end gap-2">
                    <BlogFormSelectField
                        :id="`${props.idPrefix}-font-footer`"
                        :label="props.translations.fontFooter || 'Font stopki'"
                        :model-value="props.colors['--font-footer'] || 'inherit'"
                        :options="fontOptions"
                        class="grow"
                        @update:model-value="updateValue('--font-footer', $event.toString())"
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
                                    <span class="text-xs font-medium">Korekcja wielkości</span>
                                    <span class="font-mono text-xs">{{ footerScaleValue[0] }}%</span>
                                </div>
                                <Slider
                                    :max="110"
                                    :min="80"
                                    :model-value="footerScaleValue"
                                    :step="1"
                                    @update:model-value="footerScaleValue = $event as number[]"
                                />
                            </div>
                        </PopoverContent>
                    </Popover>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <BlogFormColorField
                v-for="field in colorFields"
                :id="`${props.idPrefix}-${field.idSuffix}`"
                :key="field.key"
                :error="props.errors?.[field.key]"
                :label="props.translations[field.labelKey]"
                :model-value="props.colors[field.key]"
                :placeholder="variables[field.key]"
                :tooltip="props.translations[field.tooltipKey]"
                @update:model-value="updateValue(field.key, $event)"
            />
        </div>
    </div>
</template>
