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
];

export const MOTTO_STYLE_OPTIONS = [
    { label: 'Italic', value: 'italic' },
    { label: 'Normal', value: 'normal' },
];

export function useThemeSection(props: { colors?: ThemeColors }, emit: (e: 'update:colors', value: ThemeColors) => void) {
    function updateValue(key: string, value: string) {
        emit('update:colors', {
            ...(props.colors ?? {}),
            [key]: value,
        });
    }

    function getBaseScale(scaleKey: string): number {
        return parseFloat(props.colors?.[scaleKey] || '1');
    }

    function updateBaseScale(scaleKey: string, newBaseScale: number) {
        updateValue(scaleKey, newBaseScale.toString());
    }

    function createScaleComputed(scaleKey: string): WritableComputedRef<number[]> {
        return computed({
            get: () => [Math.round(getBaseScale(scaleKey) * 100)],
            set: (val) => updateBaseScale(scaleKey, val[0] / 100),
        });
    }

    const headerScaleValue = createScaleComputed('--header-scale');
    const bodyScaleValue = createScaleComputed('--body-scale');
    const mottoScaleValue = createScaleComputed('--motto-scale');
    const excerptScaleValue = createScaleComputed('--excerpt-scale');
    const footerScaleValue = createScaleComputed('--footer-scale');

    const colorFields = computed(() => [
        { key: '--primary', idSuffix: 'primary', labelKey: 'primary' as const, tooltipKey: 'primaryTooltip' as const },
        { key: '--background', idSuffix: 'background', labelKey: 'background' as const, tooltipKey: 'backgroundTooltip' as const },
        { key: '--secondary', idSuffix: 'secondary', labelKey: 'secondary' as const, tooltipKey: 'secondaryTooltip' as const },
        { key: '--foreground', idSuffix: 'foreground', labelKey: 'foreground' as const, tooltipKey: 'foregroundTooltip' as const },
        {
            key: '--primary-foreground',
            idSuffix: 'primary-fg',
            labelKey: 'primaryForeground' as const,
            tooltipKey: 'primaryForegroundTooltip' as const,
        },
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
                options: MOTTO_STYLE_OPTIONS,
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

    return {
        updateValue,
        colorFields,
        fontFields,
    };
}
