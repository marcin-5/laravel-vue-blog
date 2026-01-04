<script lang="ts" setup>
import CategorySelector from '@/components/CategorySelector.vue';
import BlogFormCheckboxField from '@/components/blogger/BlogFormCheckboxField.vue';
import BlogFormNumberField from '@/components/blogger/BlogFormNumberField.vue';
import BlogFormSelectField from '@/components/blogger/BlogFormSelectField.vue';
import BlogFormThemeSection from '@/components/blogger/BlogFormThemeSection.vue';
import FormSubmitActions from '@/components/blogger/FormSubmitActions.vue';
import MarkdownPreviewSection from '@/components/blogger/MarkdownPreviewSection.vue';
import PostFormField from '@/components/blogger/PostFormField.vue';
import { useBlogFormLogic } from '@/composables/useBlogFormLogic';
import { useMarkdownPreview } from '@/composables/useMarkdownPreview';
import type { AdminBlog as Blog, BlogFormData, Category } from '@/types/blog.types';
import { InertiaForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

interface Props {
    blog?: Blog;
    categories: Category[];
    isEdit?: boolean;
    idPrefix?: string;
    form?: InertiaForm<BlogFormData>;
}

interface Emits {
    (e: 'submit', form: any): void;
    (e: 'cancel'): void;
}

const props = withDefaults(defineProps<Props>(), {
    isEdit: false,
    idPrefix: 'blog',
});

const emit = defineEmits<Emits>();

const { form, fieldIdPrefix, updateCategories } = useBlogFormLogic({
    blog: props.blog,
    isEdit: props.isEdit,
    externalForm: props.form,
});

const { isPreviewMode, isFullPreview, previewLayout, previewHtml, renderMarkdown, togglePreview, toggleFullPreview, setLayout } =
    useMarkdownPreview('markdown.preview');

// Separate preview state for footer content
const {
    isPreviewMode: isFooterPreviewMode,
    isFullPreview: isFooterFullPreview,
    previewLayout: footerPreviewLayout,
    previewHtml: footerPreviewHtml,
    renderMarkdown: renderFooterMarkdown,
    togglePreview: toggleFooterPreview,
    toggleFullPreview: toggleFooterFullPreview,
    setLayout: setFooterLayout,
} = useMarkdownPreview('markdown.preview');

const baseTranslations = computed(() => ({
    name: t('blogger.form.name_label'),
    namePlaceholder: props.isEdit ? '' : t('blogger.form.name_placeholder'),
    description: t('blogger.form.description_label'),
    descriptionPlaceholder: props.isEdit ? '' : t('blogger.form.description_placeholder'),
    footer: t('blogger.form.footer_label'),
    footerPlaceholder: props.isEdit ? '' : t('blogger.form.footer_placeholder'),
    motto: t('blogger.form.motto_label'),
    mottoPlaceholder: props.isEdit ? '' : t('blogger.form.motto_placeholder'),
    mottoTooltip: t('blogger.form.motto_tooltip'),
    published: t('blogger.form.published_label'),
    locale: t('blogger.form.locale_label'),
    sidebar: t('blogger.form.sidebar_label'),
    sidebarHint: t('blogger.form.sidebar_hint'),
    pageSize: t('blogger.form.page_size_label'),
}));

const actionTranslations = computed(() => ({
    cancel: t('blogger.form.cancel_button'),
    create: t('blogger.form.create_button'),
    save: t('blogger.form.save_button'),
    apply: t('blogger.form.apply_button'),
    saving: t('blogger.form.saving_button'),
    creating: t('blogger.form.creating_button'),
}));

const previewTranslations = computed(() => ({
    preview: t('blogger.post_form.preview_button'),
    close: t('blogger.post_form.close_button'),
    fullPreview: t('blogger.post_form.full_preview_button'),
    splitView: t('blogger.post_form.split_view_button'),
    toggleLayout: previewLayout.value === 'vertical' ? t('blogger.post_form.horizontal_button') : t('blogger.post_form.vertical_button'),
    exitPreview: t('blogger.post_form.exit_preview_button'),
    markdown: t('blogger.post_form.markdown_label'),
    previewLabel: t('blogger.post_form.preview_label'),
    previewModeTitle: t('blogger.post_form.preview_mode_title'),
}));

const themeSectionTranslations = computed(() => ({
    title: t('blogger.form.theme_title'),
    description: t('blogger.form.theme_description'),
    light: t('blogger.form.theme_light'),
    dark: t('blogger.form.theme_dark'),
    advancedHint: t('blogger.form.theme_advanced_hint'),
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
}));

const localeOptions = computed(() => [
    { value: 'en', label: 'EN' },
    { value: 'pl', label: 'PL' },
]);

const themeTranslations = computed(() => ({
    background: themeSectionTranslations.value.colorBackground,
    backgroundTooltip: themeSectionTranslations.value.colorBackgroundTooltip,
    foreground: themeSectionTranslations.value.colorPrimary,
    foregroundTooltip: themeSectionTranslations.value.colorPrimaryTooltip,
    primary: themeSectionTranslations.value.colorForeground,
    primaryTooltip: themeSectionTranslations.value.colorForegroundTooltip,
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
}));

function filterErrorsByPrefix(errors: Record<string, string>, prefix: string): Record<string, string> {
    const filtered: Record<string, string> = {};
    for (const key of Object.keys(errors)) {
        if (key.startsWith(prefix)) {
            filtered[key.replace(prefix, '')] = errors[key];
        }
    }
    return filtered;
}

const themeLightErrors = computed(() => filterErrorsByPrefix(form.errors, 'theme.light.'));

const themeDarkErrors = computed(() => filterErrorsByPrefix(form.errors, 'theme.dark.'));

function handleSubmit() {
    emit('submit', form);
}

function handleApply() {
    form.patch(route('blogs.update', props.blog!.id), {
        preserveScroll: true,
        preserveState: true,
    });
}

function handleCancel() {
    emit('cancel');
}

function handleTogglePreview() {
    togglePreview(form.description || '');
}

function handleToggleFullPreview() {
    toggleFullPreview(form.description || '');
}

function handleDescriptionInput() {
    renderMarkdown(form.description || '');
}

// Footer handlers
function handleFooterTogglePreview() {
    toggleFooterPreview(form.footer || '');
}

function handleFooterToggleFullPreview() {
    toggleFooterFullPreview(form.footer || '');
}

function handleFooterInput() {
    renderFooterMarkdown(form.footer || '');
}
</script>

<template>
    <div class="rounded-md border border-sidebar-border/70 p-4 dark:border-sidebar-border">
        <form class="space-y-4" @submit.prevent="handleSubmit">
            <PostFormField
                :id="`${fieldIdPrefix}-name`"
                v-model="form.name"
                :error="form.errors.name"
                :label="baseTranslations.name"
                :placeholder="baseTranslations.namePlaceholder"
                required
                type="input"
            />

            <PostFormField
                :id="`${fieldIdPrefix}-motto`"
                v-model="form.motto"
                :error="form.errors.motto"
                :label="baseTranslations.motto"
                :placeholder="baseTranslations.mottoPlaceholder"
                :tooltip="baseTranslations.mottoTooltip"
                type="textarea"
            />

            <MarkdownPreviewSection
                :id="`${fieldIdPrefix}-description`"
                v-model="form.description"
                :error="form.errors.description"
                :is-edit="props.isEdit"
                :is-full-preview="isFullPreview"
                :is-preview-mode="isPreviewMode"
                :is-processing="form.processing"
                :label="baseTranslations.description"
                :placeholder="baseTranslations.descriptionPlaceholder"
                :preview-html="previewHtml"
                :preview-layout="previewLayout"
                :show-save-button="false"
                :translations="{
                    cancel: actionTranslations.cancel,
                    create: actionTranslations.create,
                    save: actionTranslations.save,
                    exitPreview: previewTranslations.exitPreview,
                    markdownLabel: previewTranslations.markdown,
                    previewLabel: previewTranslations.previewLabel,
                    previewModeTitle: previewTranslations.previewModeTitle,
                    toggleLayout: previewTranslations.toggleLayout,
                    closePreview: previewTranslations.close,
                    preview: previewTranslations.preview,
                    fullPreview: previewTranslations.fullPreview,
                    splitView: previewTranslations.splitView,
                }"
                @cancel="handleCancel"
                @input="handleDescriptionInput"
                @set-layout="setLayout"
                @toggle-full-preview="handleToggleFullPreview"
                @toggle-preview="handleTogglePreview"
            />

            <MarkdownPreviewSection
                :id="`${fieldIdPrefix}-footer`"
                v-model="form.footer"
                :error="form.errors.footer"
                :is-edit="props.isEdit"
                :is-full-preview="isFooterFullPreview"
                :is-preview-mode="isFooterPreviewMode"
                :is-processing="form.processing"
                :label="baseTranslations.footer"
                :placeholder="baseTranslations.footerPlaceholder"
                :preview-html="footerPreviewHtml"
                :preview-layout="footerPreviewLayout"
                :show-save-button="false"
                :translations="{
                    cancel: actionTranslations.cancel,
                    create: actionTranslations.create,
                    save: actionTranslations.save,
                    exitPreview: previewTranslations.exitPreview,
                    markdownLabel: previewTranslations.markdown,
                    previewLabel: previewTranslations.previewLabel,
                    previewModeTitle: previewTranslations.previewModeTitle,
                    toggleLayout: previewTranslations.toggleLayout,
                    closePreview: previewTranslations.close,
                    preview: previewTranslations.preview,
                    fullPreview: previewTranslations.fullPreview,
                    splitView: previewTranslations.splitView,
                }"
                @cancel="handleCancel"
                @input="handleFooterInput"
                @set-layout="setFooterLayout"
                @toggle-full-preview="handleFooterToggleFullPreview"
                @toggle-preview="handleFooterTogglePreview"
            />

            <div class="flex flex-wrap items-center gap-3">
                <BlogFormCheckboxField
                    :id="`${fieldIdPrefix}-published`"
                    v-model="form.is_published"
                    :additional-info="props.isEdit && props.blog ? `/${props.blog.slug}` : undefined"
                    :error="form.errors.is_published"
                    :label="baseTranslations.published"
                />
                <div class="ml-auto">
                    <BlogFormSelectField
                        :id="`${fieldIdPrefix}-locale`"
                        v-model="form.locale"
                        :error="form.errors.locale"
                        :label="baseTranslations.locale"
                        :options="localeOptions"
                    />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <BlogFormNumberField
                    :id="`${fieldIdPrefix}-sidebar`"
                    v-model="form.sidebar"
                    :error="form.errors.sidebar"
                    :hint="baseTranslations.sidebarHint"
                    :label="baseTranslations.sidebar"
                    :max="50"
                    :min="-50"
                />
                <BlogFormNumberField
                    :id="`${fieldIdPrefix}-page_size`"
                    v-model="form.page_size"
                    :error="form.errors.page_size"
                    :label="baseTranslations.pageSize"
                    :max="100"
                    :min="1"
                />
            </div>

            <!-- Theme editor (per-blog colors) -->
            <div class="mt-4 rounded-md border border-border p-4">
                <h3 class="mb-3 text-lg font-semibold">{{ themeSectionTranslations.title }}</h3>
                <p class="mb-3 text-sm text-muted-foreground">
                    {{ themeSectionTranslations.description }}
                </p>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <BlogFormThemeSection
                        v-model:colors="form.theme!.light!"
                        :errors="themeLightErrors"
                        :id-prefix="`${fieldIdPrefix}-theme-light`"
                        :title="themeSectionTranslations.light"
                        :translations="themeTranslations"
                    />
                    <BlogFormThemeSection
                        v-model:colors="form.theme!.dark!"
                        :errors="themeDarkErrors"
                        :id-prefix="`${fieldIdPrefix}-theme-dark`"
                        :title="themeSectionTranslations.dark"
                        :translations="themeTranslations"
                    />
                </div>
                <div class="mt-2 text-xs text-muted-foreground">
                    {{ themeSectionTranslations.advancedHint }}
                </div>
            </div>

            <CategorySelector
                :categories="props.categories"
                :id-prefix="`${fieldIdPrefix}-cat`"
                :selected-categories="form.categories"
                @update:selected-categories="updateCategories"
            />
            <div v-if="form.errors.categories" class="mt-1 text-sm font-semibold text-error">
                {{ form.errors.categories }}
            </div>

            <FormSubmitActions
                :is-edit="props.isEdit"
                :is-processing="form.processing"
                :translations="{
                    cancel: actionTranslations.cancel,
                    create: actionTranslations.create,
                    save: actionTranslations.save,
                    apply: actionTranslations.apply,
                    creating: actionTranslations.creating,
                    saving: actionTranslations.saving,
                }"
                @apply="handleApply"
                @cancel="handleCancel"
                @submit="handleSubmit"
            />
        </form>
    </div>
</template>
