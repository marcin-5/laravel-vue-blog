<script lang="ts" setup>
import BlogFormColorField from '@/components/blogger/BlogFormColorField.vue';
import BlogFormSelectField from '@/components/blogger/BlogFormSelectField.vue';
import type { ThemeColors } from '@/types/blog.types';

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

// Define the update event (following v-model convention for the colors object)
const emit = defineEmits<{
    (e: 'update:colors', value: ThemeColors): void;
}>();

// Helper function to update a specific key in an object
function updateValue(key: string, value: string) {
    const updatedColors = {
        ...props.colors,
        [key]: value,
    };
    emit('update:colors', updatedColors);
}

const fontOptions = [
    { label: 'System Default', value: 'inherit' },
    { label: 'Montserrat', value: 'var(--font-montserrat)' },
    { label: 'Nunito', value: 'var(--font-nunito)' },
    { label: 'Quicksand', value: 'var(--font-quicksand)' },
    { label: 'Recursive', value: 'var(--font-recursive)' },
    { label: 'Roboto', value: 'var(--font-roboto)' },
    { label: 'Rokkitt', value: 'var(--font-rokkitt)' },
    { label: 'Esteban', value: 'var(--font-esteban)' },
    { label: 'Inter', value: 'var(--font-inter)' },
    { label: 'Noto Serif', value: 'var(--font-noto)' },
    { label: 'Slabo 27px', value: 'var(--font-slabo)' },
];

const mottoStyleOptions = [
    { label: 'Italic', value: 'italic' },
    { label: 'Normal', value: 'normal' },
];

const footerScaleOptions = [
    { label: '100%', value: '1' },
    { label: '90%', value: '0.9' },
    { label: '80%', value: '0.8' },
];
</script>

<template>
    <div>
        <h4 class="mb-2 text-sm font-medium opacity-80">{{ props.title }}</h4>

        <div class="mb-6 grid grid-cols-1 gap-4 border-b border-border pb-6 sm:grid-cols-2">
            <BlogFormSelectField
                :id="`${props.idPrefix}-font-header`"
                :label="props.translations.fontHeader || 'Font nagłówków'"
                :model-value="props.colors['--font-header'] || 'inherit'"
                :options="fontOptions"
                @update:model-value="updateValue('--font-header', $event.toString())"
            />
            <BlogFormSelectField
                :id="`${props.idPrefix}-font-body`"
                :label="props.translations.fontBody || 'Font treści'"
                :model-value="props.colors['--font-body'] || 'inherit'"
                :options="fontOptions"
                @update:model-value="updateValue('--font-body', $event.toString())"
            />
            <div class="flex flex-col gap-2">
                <BlogFormSelectField
                    :id="`${props.idPrefix}-font-motto`"
                    :label="props.translations.fontMotto || 'Font motto/cytatów'"
                    :model-value="props.colors['--font-motto'] || 'inherit'"
                    :options="fontOptions"
                    @update:model-value="updateValue('--font-motto', $event.toString())"
                />
                <BlogFormSelectField
                    :id="`${props.idPrefix}-motto-style`"
                    :label="props.translations.mottoStyle || 'Styl motto/cytatów'"
                    :model-value="props.colors['--motto-style'] || 'italic'"
                    :options="mottoStyleOptions"
                    @update:model-value="updateValue('--motto-style', $event.toString())"
                />
            </div>
            <div class="flex flex-col gap-2">
                <BlogFormSelectField
                    :id="`${props.idPrefix}-font-footer`"
                    :label="props.translations.fontFooter || 'Font stopki'"
                    :model-value="props.colors['--font-footer'] || 'inherit'"
                    :options="fontOptions"
                    @update:model-value="updateValue('--font-footer', $event.toString())"
                />
                <BlogFormSelectField
                    :id="`${props.idPrefix}-footer-scale`"
                    :label="props.translations.footerScale || 'Wielkość stopki'"
                    :model-value="props.colors['--footer-scale'] || '1'"
                    :options="footerScaleOptions"
                    @update:model-value="updateValue('--footer-scale', $event.toString())"
                />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <BlogFormColorField
                :id="`${props.idPrefix}-background`"
                :error="props.errors?.['--background']"
                :label="props.translations.background"
                :model-value="props.colors['--background']"
                :tooltip="props.translations.backgroundTooltip"
                placeholder="#ffffff"
                @update:model-value="updateValue('--background', $event)"
            />
            <BlogFormColorField
                :id="`${props.idPrefix}-foreground`"
                :error="props.errors?.['--primary']"
                :label="props.translations.foreground"
                :model-value="props.colors['--primary']"
                :tooltip="props.translations.primaryTooltip"
                placeholder="#0a0a0a"
                @update:model-value="updateValue('--primary', $event)"
            />
            <BlogFormColorField
                :id="`${props.idPrefix}-primary`"
                :error="props.errors?.['--foreground']"
                :label="props.translations.primary"
                :model-value="props.colors['--foreground']"
                :tooltip="props.translations.foregroundTooltip"
                placeholder="#111111"
                @update:model-value="updateValue('--foreground', $event)"
            />
            <BlogFormColorField
                :id="`${props.idPrefix}-secondary`"
                :error="props.errors?.['--secondary']"
                :label="props.translations.secondary"
                :model-value="props.colors['--secondary']"
                :tooltip="props.translations.secondaryTooltip"
                placeholder="#ececec"
                @update:model-value="updateValue('--secondary', $event)"
            />
            <BlogFormColorField
                :id="`${props.idPrefix}-primary-fg`"
                :error="props.errors?.['--primary-foreground']"
                :label="props.translations.primaryForeground"
                :model-value="props.colors['--primary-foreground']"
                :tooltip="props.translations.primaryForegroundTooltip"
                placeholder="#fafafa"
                @update:model-value="updateValue('--primary-foreground', $event)"
            />
            <BlogFormColorField
                :id="`${props.idPrefix}-secondary-fg`"
                :error="props.errors?.['--secondary-foreground']"
                :label="props.translations.secondaryForeground"
                :model-value="props.colors['--secondary-foreground']"
                :tooltip="props.translations.secondaryForegroundTooltip"
                placeholder="#111111"
                @update:model-value="updateValue('--secondary-foreground', $event)"
            />
            <BlogFormColorField
                :id="`${props.idPrefix}-muted-fg`"
                :error="props.errors?.['--muted-foreground']"
                :label="props.translations.mutedForeground"
                :model-value="props.colors['--muted-foreground']"
                :tooltip="props.translations.mutedForegroundTooltip"
                placeholder="#737373"
                @update:model-value="updateValue('--muted-foreground', $event)"
            />
            <BlogFormColorField
                :id="`${props.idPrefix}-border`"
                :error="props.errors?.['--border']"
                :label="props.translations.border"
                :model-value="props.colors['--border']"
                :tooltip="props.translations.borderTooltip"
                placeholder="#e5e5e5"
                @update:model-value="updateValue('--border', $event)"
            />
            <BlogFormColorField
                :id="`${props.idPrefix}-link`"
                :error="props.errors?.['--link']"
                :label="props.translations.link"
                :model-value="props.colors['--link']"
                :tooltip="props.translations.linkTooltip"
                placeholder="#0d9488"
                @update:model-value="updateValue('--link', $event)"
            />
            <BlogFormColorField
                :id="`${props.idPrefix}-link-hover`"
                :error="props.errors?.['--link-hover']"
                :label="props.translations.linkHover"
                :model-value="props.colors['--link-hover']"
                :tooltip="props.translations.linkHoverTooltip"
                placeholder="#0f766e"
                @update:model-value="updateValue('--link-hover', $event)"
            />
            <BlogFormColorField
                :id="`${props.idPrefix}-breadcrumb-link`"
                :error="props.errors?.['--breadcrumb-link']"
                :label="props.translations.breadcrumbLink"
                :model-value="props.colors['--breadcrumb-link']"
                :tooltip="props.translations.breadcrumbLinkTooltip"
                placeholder="#534432ee"
                @update:model-value="updateValue('--breadcrumb-link', $event)"
            />
            <BlogFormColorField
                :id="`${props.idPrefix}-breadcrumb-link-active`"
                :error="props.errors?.['--breadcrumb-link-active']"
                :label="props.translations.breadcrumbLinkActive"
                :model-value="props.colors['--breadcrumb-link-active']"
                :tooltip="props.translations.breadcrumbLinkActiveTooltip"
                placeholder="#403020"
                @update:model-value="updateValue('--breadcrumb-link-active', $event)"
            />
        </div>
    </div>
</template>
