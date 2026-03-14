import type { ThemeColors } from '@/types/blog.types';
import { computed, type WritableComputedRef } from 'vue';

export interface ThemeTranslations {
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
}

type ScaleKey = '--header-scale' | '--body-scale' | '--motto-scale' | '--excerpt-scale' | '--footer-scale';

interface ColorFieldConfig {
    key: string;
    idSuffix: string;
    labelKey: keyof ThemeTranslations;
    tooltipKey: keyof ThemeTranslations;
}

interface AdditionalSelectFieldConfig {
    key: string;
    idSuffix: string;
    labelKey: keyof ThemeTranslations;
    defaultLabel: string;
    options: typeof MOTTO_STYLE_OPTIONS;
    defaultValue: string;
}

interface FontFieldDefinition {
    key: string;
    idSuffix: string;
    labelKey: keyof ThemeTranslations;
    defaultLabel: string;
    scaleKey: ScaleKey;
    scaleMin: number;
    scaleMax: number;
    additionalField?: AdditionalSelectFieldConfig;
}

interface FontFieldConfig extends FontFieldDefinition {
    scaleValue: WritableComputedRef<number[]>;
}

const DEFAULT_SCALE = 1;
const PERCENT_MULTIPLIER = 100;
const DEFAULT_SCALE_MIN = 90;
const DEFAULT_SCALE_MAX = 120;
const FOOTER_SCALE_MIN = 80;
const FOOTER_SCALE_MAX = 110;

export const FONT_OPTIONS = [
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
] as const;

export const MOTTO_STYLE_OPTIONS = [
    { label: 'Italic', value: 'italic' },
    { label: 'Normal', value: 'normal' },
] as const;

const COLOR_FIELD_CONFIG: ColorFieldConfig[] = [
    { key: '--primary', idSuffix: 'primary', labelKey: 'primary', tooltipKey: 'primaryTooltip' },
    { key: '--background', idSuffix: 'background', labelKey: 'background', tooltipKey: 'backgroundTooltip' },
    { key: '--secondary', idSuffix: 'secondary', labelKey: 'secondary', tooltipKey: 'secondaryTooltip' },
    { key: '--foreground', idSuffix: 'foreground', labelKey: 'foreground', tooltipKey: 'foregroundTooltip' },
    {
        key: '--primary-foreground',
        idSuffix: 'primary-fg',
        labelKey: 'primaryForeground',
        tooltipKey: 'primaryForegroundTooltip',
    },
    {
        key: '--muted-foreground',
        idSuffix: 'muted-fg',
        labelKey: 'mutedForeground',
        tooltipKey: 'mutedForegroundTooltip',
    },
    {
        key: '--secondary-foreground',
        idSuffix: 'secondary-fg',
        labelKey: 'secondaryForeground',
        tooltipKey: 'secondaryForegroundTooltip',
    },
    { key: '--border', idSuffix: 'border', labelKey: 'border', tooltipKey: 'borderTooltip' },
    { key: '--link', idSuffix: 'link', labelKey: 'link', tooltipKey: 'linkTooltip' },
    {
        key: '--breadcrumb-link',
        idSuffix: 'breadcrumb-link',
        labelKey: 'breadcrumbLink',
        tooltipKey: 'breadcrumbLinkTooltip',
    },
    { key: '--link-hover', idSuffix: 'link-hover', labelKey: 'linkHover', tooltipKey: 'linkHoverTooltip' },
    {
        key: '--breadcrumb-link-active',
        idSuffix: 'breadcrumb-link-active',
        labelKey: 'breadcrumbLinkActive',
        tooltipKey: 'breadcrumbLinkActiveTooltip',
    },
    { key: '--card', idSuffix: 'card', labelKey: 'card', tooltipKey: 'cardTooltip' },
];

const FONT_FIELD_DEFINITIONS: FontFieldDefinition[] = [
    {
        key: '--font-header',
        idSuffix: 'font-header',
        labelKey: 'fontHeader',
        defaultLabel: 'Font nagłówków',
        scaleKey: '--header-scale',
        scaleMin: DEFAULT_SCALE_MIN,
        scaleMax: DEFAULT_SCALE_MAX,
    },
    {
        key: '--font-body',
        idSuffix: 'font-body',
        labelKey: 'fontBody',
        defaultLabel: 'Font treści',
        scaleKey: '--body-scale',
        scaleMin: DEFAULT_SCALE_MIN,
        scaleMax: DEFAULT_SCALE_MAX,
    },
    {
        key: '--font-motto',
        idSuffix: 'font-motto',
        labelKey: 'fontMotto',
        defaultLabel: 'Font motto/cytatów',
        scaleKey: '--motto-scale',
        scaleMin: DEFAULT_SCALE_MIN,
        scaleMax: DEFAULT_SCALE_MAX,
        additionalField: {
            key: '--motto-style',
            idSuffix: 'motto-style',
            labelKey: 'mottoStyle',
            defaultLabel: 'Styl motto/cytatów',
            options: MOTTO_STYLE_OPTIONS,
            defaultValue: 'italic',
        },
    },
    {
        key: '--font-excerpt',
        idSuffix: 'font-excerpt',
        labelKey: 'fontExcerpt',
        defaultLabel: 'Font tekstu zachęty',
        scaleKey: '--excerpt-scale',
        scaleMin: DEFAULT_SCALE_MIN,
        scaleMax: DEFAULT_SCALE_MAX,
    },
    {
        key: '--font-footer',
        idSuffix: 'font-footer',
        labelKey: 'fontFooter',
        defaultLabel: 'Font stopki',
        scaleKey: '--footer-scale',
        scaleMin: FOOTER_SCALE_MIN,
        scaleMax: FOOTER_SCALE_MAX,
    },
];

export function useThemeSection(props: { colors?: ThemeColors }, emit: (e: 'update:colors', value: ThemeColors) => void) {
    function updateValue(key: string, value: string) {
        emit('update:colors', {
            ...(props.colors ?? {}),
            [key]: value,
        });
    }

    function getScaleValue(scaleKey: ScaleKey): number {
        return Number.parseFloat(props.colors?.[scaleKey] ?? String(DEFAULT_SCALE));
    }

    function updateScaleValue(scaleKey: ScaleKey, nextScale: number) {
        updateValue(scaleKey, nextScale.toString());
    }

    function createScaleComputed(scaleKey: ScaleKey): WritableComputedRef<number[]> {
        return computed({
            get: () => [Math.round(getScaleValue(scaleKey) * PERCENT_MULTIPLIER)],
            set: ([value]) => updateScaleValue(scaleKey, value / PERCENT_MULTIPLIER),
        });
    }

    const scaleValues: Record<ScaleKey, WritableComputedRef<number[]>> = {
        '--header-scale': createScaleComputed('--header-scale'),
        '--body-scale': createScaleComputed('--body-scale'),
        '--motto-scale': createScaleComputed('--motto-scale'),
        '--excerpt-scale': createScaleComputed('--excerpt-scale'),
        '--footer-scale': createScaleComputed('--footer-scale'),
    };

    const colorFields = computed(() => COLOR_FIELD_CONFIG);

    const fontFields = computed<FontFieldConfig[]>(() =>
        FONT_FIELD_DEFINITIONS.map((field) => ({
            ...field,
            scaleValue: scaleValues[field.scaleKey],
        })),
    );

    return {
        updateValue,
        colorFields,
        fontFields,
    };
}
