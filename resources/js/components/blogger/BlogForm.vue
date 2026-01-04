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
import type { AdminBlog as Blog, BlogFormData, Category } from '@/types/blog.types';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

interface Props {
    blog?: Blog;
    categories: Category[];
    isEdit?: boolean;
    idPrefix?: string;
    form?: BlogFormData;
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
    cancel: t('blogger.form.cancel_button'),
    create: t('blogger.form.create_button'),
    save: t('blogger.form.save_button'),
    saving: t('blogger.form.saving_button'),
    creating: t('blogger.form.creating_button'),
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

            <!-- Theme editor (per-blog colors) -->
            <div class="mt-4 rounded-md border border-border p-4">
                <h3 class="mb-3 text-lg font-semibold">Theme</h3>
                <p class="mb-3 text-sm text-muted-foreground">
                    Define per-blog CSS color variables. Leave empty to use application defaults. Values should be valid CSS colors (e.g., #ffffff or
                    hsl()).
                </p>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <h4 class="mb-2 text-sm font-medium opacity-80">Light</h4>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <PostFormField
                                :id="`${fieldIdPrefix}-theme-light-background`"
                                v-model="form.theme!.light!['--background']"
                                :error="form.errors['theme.light']"
                                label="--background"
                                type="custom"
                            >
                                <input
                                    :value="form.theme?.light?.['--background']"
                                    class="w-full rounded border border-border bg-background p-2 text-foreground"
                                    placeholder="#ffffff"
                                    type="text"
                                    @input="form.theme!.light!['--background'] = ($event.target as HTMLInputElement).value"
                                />
                            </PostFormField>
                            <PostFormField
                                :id="`${fieldIdPrefix}-theme-light-foreground`"
                                v-model="form.theme!.light!['--foreground']"
                                :error="form.errors['theme.light']"
                                label="--foreground"
                                type="custom"
                            >
                                <input
                                    :value="form.theme?.light?.['--foreground']"
                                    class="w-full rounded border border-border bg-background p-2 text-foreground"
                                    placeholder="#0a0a0a"
                                    type="text"
                                    @input="form.theme!.light!['--foreground'] = ($event.target as HTMLInputElement).value"
                                />
                            </PostFormField>
                            <PostFormField
                                :id="`${fieldIdPrefix}-theme-light-primary`"
                                v-model="form.theme!.light!['--primary']"
                                :error="form.errors['theme.light']"
                                label="--primary"
                                type="custom"
                            >
                                <input
                                    :value="form.theme?.light?.['--primary']"
                                    class="w-full rounded border border-border bg-background p-2 text-foreground"
                                    placeholder="#111111"
                                    type="text"
                                    @input="form.theme!.light!['--primary'] = ($event.target as HTMLInputElement).value"
                                />
                            </PostFormField>
                            <PostFormField
                                :id="`${fieldIdPrefix}-theme-light-primary-fg`"
                                v-model="form.theme!.light!['--primary-foreground']"
                                :error="form.errors['theme.light']"
                                label="--primary-foreground"
                                type="custom"
                            >
                                <input
                                    :value="form.theme?.light?.['--primary-foreground']"
                                    class="w-full rounded border border-border bg-background p-2 text-foreground"
                                    placeholder="#fafafa"
                                    type="text"
                                    @input="form.theme!.light!['--primary-foreground'] = ($event.target as HTMLInputElement).value"
                                />
                            </PostFormField>
                            <PostFormField
                                :id="`${fieldIdPrefix}-theme-light-secondary`"
                                v-model="form.theme!.light!['--secondary']"
                                :error="form.errors['theme.light']"
                                label="--secondary"
                                type="custom"
                            >
                                <input
                                    :value="form.theme?.light?.['--secondary']"
                                    class="w-full rounded border border-border bg-background p-2 text-foreground"
                                    placeholder="#ececec"
                                    type="text"
                                    @input="form.theme!.light!['--secondary'] = ($event.target as HTMLInputElement).value"
                                />
                            </PostFormField>
                            <PostFormField
                                :id="`${fieldIdPrefix}-theme-light-secondary-fg`"
                                v-model="form.theme!.light!['--secondary-foreground']"
                                :error="form.errors['theme.light']"
                                label="--secondary-foreground"
                                type="custom"
                            >
                                <input
                                    :value="form.theme?.light?.['--secondary-foreground']"
                                    class="w-full rounded border border-border bg-background p-2 text-foreground"
                                    placeholder="#111111"
                                    type="text"
                                    @input="form.theme!.light!['--secondary-foreground'] = ($event.target as HTMLInputElement).value"
                                />
                            </PostFormField>
                        </div>
                    </div>
                    <div>
                        <h4 class="mb-2 text-sm font-medium opacity-80">Dark</h4>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <PostFormField
                                :id="`${fieldIdPrefix}-theme-dark-background`"
                                v-model="form.theme!.dark!['--background']"
                                :error="form.errors['theme.dark']"
                                label="--background"
                                type="custom"
                            >
                                <input
                                    :value="form.theme?.dark?.['--background']"
                                    class="w-full rounded border border-border bg-background p-2 text-foreground"
                                    placeholder="#0a0a0a"
                                    type="text"
                                    @input="form.theme!.dark!['--background'] = ($event.target as HTMLInputElement).value"
                                />
                            </PostFormField>
                            <PostFormField
                                :id="`${fieldIdPrefix}-theme-dark-foreground`"
                                v-model="form.theme!.dark!['--foreground']"
                                :error="form.errors['theme.dark']"
                                label="--foreground"
                                type="custom"
                            >
                                <input
                                    :value="form.theme?.dark?.['--foreground']"
                                    class="w-full rounded border border-border bg-background p-2 text-foreground"
                                    placeholder="#f7f7f7"
                                    type="text"
                                    @input="form.theme!.dark!['--foreground'] = ($event.target as HTMLInputElement).value"
                                />
                            </PostFormField>
                            <PostFormField
                                :id="`${fieldIdPrefix}-theme-dark-primary`"
                                v-model="form.theme!.dark!['--primary']"
                                :error="form.errors['theme.dark']"
                                label="--primary"
                                type="custom"
                            >
                                <input
                                    :value="form.theme?.dark?.['--primary']"
                                    class="w-full rounded border border-border bg-background p-2 text-foreground"
                                    placeholder="#f7f7f7"
                                    type="text"
                                    @input="form.theme!.dark!['--primary'] = ($event.target as HTMLInputElement).value"
                                />
                            </PostFormField>
                            <PostFormField
                                :id="`${fieldIdPrefix}-theme-dark-primary-fg`"
                                v-model="form.theme!.dark!['--primary-foreground']"
                                :error="form.errors['theme.dark']"
                                label="--primary-foreground"
                                type="custom"
                            >
                                <input
                                    :value="form.theme?.dark?.['--primary-foreground']"
                                    class="w-full rounded border border-border bg-background p-2 text-foreground"
                                    placeholder="#111111"
                                    type="text"
                                    @input="form.theme!.dark!['--primary-foreground'] = ($event.target as HTMLInputElement).value"
                                />
                            </PostFormField>
                            <PostFormField
                                :id="`${fieldIdPrefix}-theme-dark-secondary`"
                                v-model="form.theme!.dark!['--secondary']"
                                :error="form.errors['theme.dark']"
                                label="--secondary"
                                type="custom"
                            >
                                <input
                                    :value="form.theme?.dark?.['--secondary']"
                                    class="w-full rounded border border-border bg-background p-2 text-foreground"
                                    placeholder="#222222"
                                    type="text"
                                    @input="form.theme!.dark!['--secondary'] = ($event.target as HTMLInputElement).value"
                                />
                            </PostFormField>
                            <PostFormField
                                :id="`${fieldIdPrefix}-theme-dark-secondary-fg`"
                                v-model="form.theme!.dark!['--secondary-foreground']"
                                :error="form.errors['theme.dark']"
                                label="--secondary-foreground"
                                type="custom"
                            >
                                <input
                                    :value="form.theme?.dark?.['--secondary-foreground']"
                                    class="w-full rounded border border-border bg-background p-2 text-foreground"
                                    placeholder="#fafafa"
                                    type="text"
                                    @input="form.theme!.dark!['--secondary-foreground'] = ($event.target as HTMLInputElement).value"
                                />
                            </PostFormField>
                        </div>
                    </div>
                </div>
                <div class="mt-2 text-xs text-muted-foreground">
                    Advanced: you may add any other CSS variables used by the app (e.g., --accent, --muted, --border) by editing JSON payload via API.
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
