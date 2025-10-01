<script lang="ts" setup>
import CategorySelector from '@/components/CategorySelector.vue';
import BlogFormCheckboxField from '@/components/blog/BlogFormCheckboxField.vue';
import BlogFormNumberField from '@/components/blog/BlogFormNumberField.vue';
import BlogFormSelectField from '@/components/blog/BlogFormSelectField.vue';
import FormSubmitActions from '@/components/blog/FormSubmitActions.vue';
import MarkdownPreviewSection from '@/components/blog/MarkdownPreviewSection.vue';
import PostFormField from '@/components/blog/PostFormField.vue';
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

const {
    isPreviewMode,
    isFullPreview,
    previewLayout,
    previewHtml,
    renderMarkdown,
    togglePreview,
    toggleFullPreview,
    setLayoutHorizontal,
    setLayoutVertical,
} = useMarkdownPreview('markdown.preview');

const translationKeys = computed(() => ({
    name: t('blogs.form.name_label'),
    namePlaceholder: props.isEdit ? '' : t('blogs.form.name_placeholder'),
    description: t('blogs.form.description_label'),
    descriptionPlaceholder: props.isEdit ? '' : t('blogs.form.description_placeholder'),
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
    horizontal: t('blogs.post_form.horizontal_button'),
    vertical: t('blogs.post_form.vertical_button'),
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
                :translations="{
                    cancel: translationKeys.cancel,
                    create: translationKeys.create,
                    save: translationKeys.save,
                    exitPreview: translationKeys.exitPreview,
                    markdownLabel: translationKeys.markdown,
                    previewLabel: translationKeys.previewLabel,
                    previewModeTitle: translationKeys.previewModeTitle,
                    horizontal: translationKeys.horizontal,
                    vertical: translationKeys.vertical,
                    closePreview: translationKeys.close,
                    preview: translationKeys.preview,
                    fullPreview: translationKeys.fullPreview,
                    splitView: translationKeys.splitView,
                }"
                @cancel="handleCancel"
                @input="handleDescriptionInput"
                @set-layout-horizontal="setLayoutHorizontal"
                @set-layout-vertical="setLayoutVertical"
                @submit="handleSubmit"
                @toggle-full-preview="handleToggleFullPreview"
                @toggle-preview="handleTogglePreview"
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
            <div v-if="form.errors.categories" class="mt-1 text-sm text-red-600">
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
