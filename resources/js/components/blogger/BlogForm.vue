<script lang="ts" setup>
import CategorySelector from '@/components/CategorySelector.vue';
import BlogFormCheckboxField from '@/components/blogger/BlogFormCheckboxField.vue';
import BlogFormNumberField from '@/components/blogger/BlogFormNumberField.vue';
import BlogFormSelectField from '@/components/blogger/BlogFormSelectField.vue';
import FormSubmitActions from '@/components/blogger/FormSubmitActions.vue';
import MarkdownPreviewSection from '@/components/blogger/MarkdownPreviewSection.vue';
import PostFormField from '@/components/blogger/PostFormField.vue';
import { useBlogFormLogic } from '@/composables/useBlogFormLogic';
import { useMarkdownPreview } from '@/composables/useMarkdownPreview';
import { ensureNamespace } from '@/i18n';
import type { Blog, Category } from '@/types';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { locale, t } = useI18n();
await ensureNamespace(locale.value, 'blogs');

interface Props {
    blog?: Blog;
    categories: Category[];
    isEdit?: boolean;
    idPrefix?: string;
    form?: any;
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

const translationKeys = computed(() => ({
    name: t('blogs.form.name_label'),
    namePlaceholder: props.isEdit ? '' : t('blogs.form.name_placeholder'),
    description: t('blogs.form.description_label'),
    descriptionPlaceholder: props.isEdit ? '' : t('blogs.form.description_placeholder'),
    footer: t('blogs.form.footer_label'),
    footerPlaceholder: props.isEdit ? '' : t('blogs.form.footer_placeholder'),
    motto: t('blogs.form.motto_label'),
    mottoPlaceholder: props.isEdit ? '' : t('blogs.form.motto_placeholder'),
    mottoTooltip: t('blogs.form.motto_tooltip'),
    published: t('blogs.form.published_label'),
    locale: t('blogs.form.locale_label'),
    sidebar: t('blogs.form.sidebar_label'),
    sidebarHint: t('blogs.form.sidebar_hint'),
    pageSize: t('blogs.form.page_size_label'),
    cancel: t('blogs.form.cancel_button'),
    create: t('blogs.form.create_button'),
    save: t('blogs.form.save_button'),
    saving: t('blogs.form.saving_button'),
    creating: t('blogs.form.creating_button'),
    preview: t('blogs.post_form.preview_button'),
    close: t('blogs.post_form.close_button'),
    fullPreview: t('blogs.post_form.full_preview_button'),
    splitView: t('blogs.post_form.split_view_button'),
    toggleLayout: previewLayout.value === 'vertical' ? t('blogs.post_form.horizontal_button') : t('blogs.post_form.vertical_button'),
    exitPreview: t('blogs.post_form.exit_preview_button'),
    markdown: t('blogs.post_form.markdown_label'),
    previewLabel: t('blogs.post_form.preview_label'),
    previewModeTitle: t('blogs.post_form.preview_mode_title'),
}));

const localeOptions = computed(() => [
    { value: 'en', label: 'EN' },
    { value: 'pl', label: 'PL' },
]);

function handleSubmit() {
    emit('submit', form);
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
                :label="translationKeys.name"
                :placeholder="translationKeys.namePlaceholder"
                required
                type="input"
            />

            <PostFormField
                :id="`${fieldIdPrefix}-motto`"
                v-model="form.motto"
                :error="form.errors.motto"
                :label="translationKeys.motto"
                :placeholder="translationKeys.mottoPlaceholder"
                :tooltip="translationKeys.mottoTooltip"
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
                :label="translationKeys.description"
                :placeholder="translationKeys.descriptionPlaceholder"
                :preview-html="previewHtml"
                :preview-layout="previewLayout"
                :show-save-button="false"
                :translations="{
                    cancel: translationKeys.cancel,
                    create: translationKeys.create,
                    save: translationKeys.save,
                    exitPreview: translationKeys.exitPreview,
                    markdownLabel: translationKeys.markdown,
                    previewLabel: translationKeys.previewLabel,
                    previewModeTitle: translationKeys.previewModeTitle,
                    toggleLayout: translationKeys.toggleLayout,
                    closePreview: translationKeys.close,
                    preview: translationKeys.preview,
                    fullPreview: translationKeys.fullPreview,
                    splitView: translationKeys.splitView,
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
                :label="translationKeys.footer"
                :placeholder="translationKeys.footerPlaceholder"
                :preview-html="footerPreviewHtml"
                :preview-layout="footerPreviewLayout"
                :show-save-button="false"
                :translations="{
                    cancel: translationKeys.cancel,
                    create: translationKeys.create,
                    save: translationKeys.save,
                    exitPreview: translationKeys.exitPreview,
                    markdownLabel: translationKeys.markdown,
                    previewLabel: translationKeys.previewLabel,
                    previewModeTitle: translationKeys.previewModeTitle,
                    toggleLayout: translationKeys.toggleLayout,
                    closePreview: translationKeys.close,
                    preview: translationKeys.preview,
                    fullPreview: translationKeys.fullPreview,
                    splitView: translationKeys.splitView,
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
                    :label="translationKeys.published"
                />
                <div class="ml-auto">
                    <BlogFormSelectField
                        :id="`${fieldIdPrefix}-locale`"
                        v-model="form.locale"
                        :error="form.errors.locale"
                        :label="translationKeys.locale"
                        :options="localeOptions"
                    />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <BlogFormNumberField
                    :id="`${fieldIdPrefix}-sidebar`"
                    v-model="form.sidebar"
                    :error="form.errors.sidebar"
                    :hint="translationKeys.sidebarHint"
                    :label="translationKeys.sidebar"
                    :max="50"
                    :min="-50"
                />
                <BlogFormNumberField
                    :id="`${fieldIdPrefix}-page_size`"
                    v-model="form.page_size"
                    :error="form.errors.page_size"
                    :label="translationKeys.pageSize"
                    :max="100"
                    :min="1"
                />
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
                    cancel: translationKeys.cancel,
                    create: translationKeys.create,
                    save: translationKeys.save,
                    creating: translationKeys.creating,
                    saving: translationKeys.saving,
                }"
                @cancel="handleCancel"
                @submit="handleSubmit"
            />
        </form>
    </div>
</template>
