import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import type { useMarkdownPreviewSection } from './useMarkdownPreviewSection';

export function useBloggerFormTranslations() {
    const { t } = useI18n();

    const actionTranslations = computed(() => ({
        cancel: t('blogger.form.cancel_button'),
        create: t('blogger.form.create_button'),
        save: t('blogger.form.save_button'),
        apply: t('blogger.form.apply_button'),
        saving: t('blogger.form.saving_button'),
        creating: t('blogger.form.creating_button'),
    }));

    const themeSectionTranslations = computed(() => ({
        title: t('blogger.form.theme_title'),
        description: t('blogger.form.theme_description'),
        light: t('blogger.form.theme_light'),
        dark: t('blogger.form.theme_dark'),
        advancedHint: t('blogger.form.theme_advanced_hint'),
        export: t('blogger.form.theme_export'),
        import: t('blogger.form.theme_import'),
        importError: t('blogger.form.theme_import_error'),
        importSuccess: t('blogger.form.theme_import_success'),
        colorBackground: t('blogger.form.theme_color_background'),
        colorBackgroundTooltip: t('blogger.form.theme_color_background_tooltip'),
        colorForeground: t('blogger.form.theme_color_foreground'),
        colorForegroundTooltip: t('blogger.form.theme_color_foreground_tooltip'),
        colorPrimary: t('blogger.form.theme_color_primary'),
        colorPrimaryTooltip: t('blogger.form.theme_color_primary_tooltip'),
        colorPrimaryForeground: t('blogger.form.theme_color_primary_foreground'),
        colorPrimaryForegroundTooltip: t('blogger.form.theme_color_primary_foreground_tooltip'),
        colorSecondary: t('blogger.form.theme_color_secondary'),
        colorSecondaryTooltip: t('blogger.form.theme_color_secondary_tooltip'),
        colorSecondaryForeground: t('blogger.form.theme_color_secondary_foreground'),
        colorSecondaryForegroundTooltip: t('blogger.form.theme_color_secondary_foreground_tooltip'),
        colorMutedForeground: t('blogger.form.theme_color_muted_foreground'),
        colorMutedForegroundTooltip: t('blogger.form.theme_color_muted_foreground_tooltip'),
        colorBorder: t('blogger.form.theme_color_border'),
        colorBorderTooltip: t('blogger.form.theme_color_border_tooltip'),
        colorLink: t('blogger.form.theme_color_link'),
        colorLinkTooltip: t('blogger.form.theme_color_link_tooltip'),
        colorLinkHover: t('blogger.form.theme_color_link_hover'),
        colorLinkHoverTooltip: t('blogger.form.theme_color_link_hover_tooltip'),
        colorBreadcrumbLink: t('blogger.form.theme_color_breadcrumb_link'),
        colorBreadcrumbLinkTooltip: t('blogger.form.theme_color_breadcrumb_link_tooltip'),
        colorBreadcrumbLinkActive: t('blogger.form.theme_color_breadcrumb_link_active'),
        colorBreadcrumbLinkActiveTooltip: t('blogger.form.theme_color_breadcrumb_link_active_tooltip'),
        colorCard: t('blogger.form.theme_color_card'),
        colorCardTooltip: t('blogger.form.theme_color_card_tooltip'),
    }));

    const themeTranslations = computed(() => ({
        exportTooltip: themeSectionTranslations.value.export,
        importTooltip: themeSectionTranslations.value.import,
        importError: themeSectionTranslations.value.importError,
        importSuccess: themeSectionTranslations.value.importSuccess,
        background: themeSectionTranslations.value.colorBackground,
        backgroundTooltip: themeSectionTranslations.value.colorBackgroundTooltip,
        foreground: themeSectionTranslations.value.colorForeground,
        foregroundTooltip: themeSectionTranslations.value.colorForegroundTooltip,
        primary: themeSectionTranslations.value.colorPrimary,
        primaryTooltip: themeSectionTranslations.value.colorPrimaryTooltip,
        primaryForeground: themeSectionTranslations.value.colorPrimaryForeground,
        primaryForegroundTooltip: themeSectionTranslations.value.colorPrimaryForegroundTooltip,
        secondary: themeSectionTranslations.value.colorSecondary,
        secondaryTooltip: themeSectionTranslations.value.colorSecondaryTooltip,
        secondaryForeground: themeSectionTranslations.value.colorSecondaryForeground,
        secondaryForegroundTooltip: themeSectionTranslations.value.colorSecondaryForegroundTooltip,
        mutedForeground: themeSectionTranslations.value.colorMutedForeground,
        mutedForegroundTooltip: themeSectionTranslations.value.colorMutedForegroundTooltip,
        border: themeSectionTranslations.value.colorBorder,
        borderTooltip: themeSectionTranslations.value.colorBorderTooltip,
        link: themeSectionTranslations.value.colorLink,
        linkTooltip: themeSectionTranslations.value.colorLinkTooltip,
        linkHover: themeSectionTranslations.value.colorLinkHover,
        linkHoverTooltip: themeSectionTranslations.value.colorLinkHoverTooltip,
        breadcrumbLink: themeSectionTranslations.value.colorBreadcrumbLink,
        breadcrumbLinkTooltip: themeSectionTranslations.value.colorBreadcrumbLinkTooltip,
        breadcrumbLinkActive: themeSectionTranslations.value.colorBreadcrumbLinkActive,
        breadcrumbLinkActiveTooltip: themeSectionTranslations.value.colorBreadcrumbLinkActiveTooltip,
        card: themeSectionTranslations.value.colorCard,
        cardTooltip: themeSectionTranslations.value.colorCardTooltip,
        fontHeader: t('blogger.form.theme_font_header'),
        fontBody: t('blogger.form.theme_font_body'),
        fontMotto: t('blogger.form.theme_font_motto'),
        fontExcerpt: t('blogger.form.theme_font_excerpt'),
        fontFooter: t('blogger.form.theme_font_footer'),
        fontScaleCorrection: t('blogger.form.theme_font_scale_correction'),
        mottoStyle: t('blogger.form.theme_motto_style'),
        footerScale: t('blogger.form.theme_footer_scale'),
    }));

    function createMarkdownTranslations(previewSection: ReturnType<typeof useMarkdownPreviewSection>) {
        return computed(() => ({
            cancel: actionTranslations.value.cancel,
            create: actionTranslations.value.create,
            save: actionTranslations.value.save,
            exitPreview: t('blogger.post_form.exit_preview_button'),
            markdownLabel: t('blogger.post_form.markdown_label'),
            previewLabel: t('blogger.post_form.preview_label'),
            previewModeTitle: t('blogger.post_form.preview_mode_title'),
            toggleLayout:
                previewSection.previewLayout.value === 'vertical' ? t('blogger.post_form.horizontal_button') : t('blogger.post_form.vertical_button'),
            closePreview: t('blogger.post_form.close_button'),
            preview: t('blogger.post_form.preview_button'),
            fullPreview: t('blogger.post_form.full_preview_button'),
            splitView: t('blogger.post_form.split_view_button'),
        }));
    }

    return {
        actionTranslations,
        themeSectionTranslations,
        themeTranslations,
        createMarkdownTranslations,
    };
}
