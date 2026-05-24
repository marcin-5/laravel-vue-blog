import { type MarkdownPreviewSection } from '@/types/blog.types';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

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
        exportTooltip: t('blogger.form.theme_export'),
        importTooltip: t('blogger.form.theme_import'),
        importError: t('blogger.form.theme_import_error'),
        importSuccess: t('blogger.form.theme_import_success'),
        background: t('blogger.form.theme_color_background'),
        backgroundTooltip: t('blogger.form.theme_color_background_tooltip'),
        foreground: t('blogger.form.theme_color_foreground'),
        foregroundTooltip: t('blogger.form.theme_color_foreground_tooltip'),
        primary: t('blogger.form.theme_color_primary'),
        primaryTooltip: t('blogger.form.theme_color_primary_tooltip'),
        primaryForeground: t('blogger.form.theme_color_primary_foreground'),
        primaryForegroundTooltip: t('blogger.form.theme_color_primary_foreground_tooltip'),
        secondary: t('blogger.form.theme_color_secondary'),
        secondaryTooltip: t('blogger.form.theme_color_secondary_tooltip'),
        secondaryForeground: t('blogger.form.theme_color_secondary_foreground'),
        secondaryForegroundTooltip: t('blogger.form.theme_color_secondary_foreground_tooltip'),
        mutedForeground: t('blogger.form.theme_color_muted_foreground'),
        mutedForegroundTooltip: t('blogger.form.theme_color_muted_foreground_tooltip'),
        border: t('blogger.form.theme_color_border'),
        borderTooltip: t('blogger.form.theme_color_border_tooltip'),
        link: t('blogger.form.theme_color_link'),
        linkTooltip: t('blogger.form.theme_color_link_tooltip'),
        linkHover: t('blogger.form.theme_color_link_hover'),
        linkHoverTooltip: t('blogger.form.theme_color_link_hover_tooltip'),
        breadcrumbLink: t('blogger.form.theme_color_breadcrumb_link'),
        breadcrumbLinkTooltip: t('blogger.form.theme_color_breadcrumb_link_tooltip'),
        breadcrumbLinkActive: t('blogger.form.theme_color_breadcrumb_link_active'),
        breadcrumbLinkActiveTooltip: t('blogger.form.theme_color_breadcrumb_link_active_tooltip'),
        card: t('blogger.form.theme_color_card'),
        cardTooltip: t('blogger.form.theme_color_card_tooltip'),
        fontHeader: t('blogger.form.theme_font_header'),
        fontBody: t('blogger.form.theme_font_body'),
        fontMotto: t('blogger.form.theme_font_motto'),
        fontExcerpt: t('blogger.form.theme_font_excerpt'),
        fontFooter: t('blogger.form.theme_font_footer'),
        fontScaleCorrection: t('blogger.form.theme_font_scale_correction'),
        mottoStyle: t('blogger.form.theme_motto_style'),
        footerScale: t('blogger.form.theme_footer_scale'),
    }));

    function createMarkdownTranslations(previewSection: MarkdownPreviewSection) {
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
        createMarkdownTranslations,
    };
}
