<script lang="ts" setup>
import FormSubmitActions from '@/components/blog/FormSubmitActions.vue';
import MarkdownPreviewSection from '@/components/blog/MarkdownPreviewSection.vue';
import PostFormField from '@/components/blog/PostFormField.vue';
import { useMarkdownPreview } from '@/composables/useMarkdownPreview';
import { ensureNamespace } from '@/i18n';
import type { PostItem } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { locale, t } = useI18n();
await ensureNamespace(locale.value, 'blogs');

interface Props {
    post?: PostItem;
    blogId?: number;
    isEdit?: boolean;
    idPrefix?: string;
    form?: any; // External form instance
}

interface Emits {
    (e: 'submit', form: any): void;
    (e: 'cancel'): void;
}

const props = withDefaults(defineProps<Props>(), {
    isEdit: false,
    idPrefix: 'post',
});

const emit = defineEmits<Emits>();

// Preview functionality using composable
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

// Use external form if provided, otherwise create internal form
const form =
    props.form ||
    useForm({
        blog_id: props.blogId || props.post?.blog_id || 0,
        title: props.post?.title || '',
        excerpt: props.post?.excerpt || '',
        content: props.post?.content || '',
        is_published: props.post?.is_published || false,
    });

const fieldIdPrefix = computed(() => props.idPrefix);

// Consolidated translation keys
const translationKeys = computed(() => ({
    title: props.isEdit ? t('blogs.post_form.title_label') : t('blogs.post_form.post_title_label'),
    titlePlaceholder: props.isEdit ? '' : t('blogs.post_form.title_placeholder'),
    excerpt: t('blogs.post_form.excerpt_label'),
    excerptPlaceholder: props.isEdit ? '' : t('blogs.post_form.excerpt_placeholder'),
    content: t('blogs.post_form.content_label'),
    contentPlaceholder: props.isEdit ? '' : t('blogs.post_form.content_placeholder'),
    published: props.isEdit ? t('blogs.post_form.published_label') : t('blogs.post_form.publish_now_label'),
    cancel: t('blogs.post_form.cancel_button'),
    create: t('blogs.post_form.create_post_button'),
    creating: t('blogs.post_form.creating_button'),
    save: t('blogs.post_form.save_post_button'),
    saving: t('blogs.post_form.saving_button'),
    // Preview-related translations for MarkdownPreviewSection
    preview: t('blogs.post_form.preview_button'),
    closePreview: t('blogs.post_form.close_button'),
    fullPreview: t('blogs.post_form.full_preview_button'),
    splitView: t('blogs.post_form.split_view_button'),
    horizontal: t('blogs.post_form.horizontal_button'),
    vertical: t('blogs.post_form.vertical_button'),
    exitPreview: t('blogs.post_form.exit_preview_button'),
    markdownLabel: t('blogs.post_form.markdown_label'),
    previewLabel: t('blogs.post_form.preview_label'),
    previewModeTitle: t('blogs.post_form.preview_mode_title'),
}));

const updateFormFromPost = (post: PostItem) => {
    form.blog_id = post.blog_id;
    form.title = post.title;
    form.excerpt = post.excerpt ?? '';
    form.content = post.content ?? '';
    form.is_published = post.is_published;
};

// Update form when post prop changes (for edit mode) - only if using internal form
if (!props.form) {
    watch(
        () => props.post,
        (newPost) => {
            if (newPost) {
                updateFormFromPost(newPost);
            }
        },
        { immediate: true },
    );

    // Update blog_id when blogId prop changes (for create mode) - only if using internal form
    watch(
        () => props.blogId,
        (newBlogId) => {
            if (newBlogId && !props.isEdit) {
                form.blog_id = newBlogId;
            }
        },
        { immediate: true },
    );
}

// Debounced markdown rendering for better performance
const debouncedRenderMarkdown = useDebounceFn((content: string) => {
    renderMarkdown(content);
}, 300);

function handleSubmit() {
    emit('submit', form);
}

function handleCancel() {
    emit('cancel');
}

function handleTogglePreview() {
    togglePreview(form.content);
}

function handleToggleFullPreview() {
    toggleFullPreview(form.content);
}

function handleContentInput() {
    debouncedRenderMarkdown(form.content);
}

</script>

<template>
    <div class="mt-4 border-t pt-4">
        <form class="space-y-4" @submit.prevent="handleSubmit">
            <PostFormField
                :id="`${fieldIdPrefix}-title`"
                v-model="form.title"
                :error="form.errors?.title"
                :label="translationKeys.title"
                :placeholder="translationKeys.titlePlaceholder"
                required
                type="input"
            />

            <PostFormField
                :id="`${fieldIdPrefix}-excerpt`"
                v-model="form.excerpt"
                :error="form.errors?.excerpt"
                :label="translationKeys.excerpt"
                :placeholder="translationKeys.excerptPlaceholder"
                :rows="2"
                type="textarea"
            />

            <MarkdownPreviewSection
                :id="`${fieldIdPrefix}-content`"
                v-model="form.content"
                :error="form.errors.content"
                :is-edit="props.isEdit"
                :is-full-preview="isFullPreview"
                :is-preview-mode="isPreviewMode"
                :is-processing="form.processing"
                :label="translationKeys.content"
                :placeholder="translationKeys.contentPlaceholder"
                :preview-html="previewHtml"
                :preview-layout="previewLayout"
                :rows="props.isEdit ? 4 : 5"
                :translations="{
                    cancel: translationKeys.cancel,
                    create: translationKeys.create,
                    save: translationKeys.save,
                    exitPreview: translationKeys.exitPreview,
                    markdownLabel: translationKeys.markdownLabel,
                    previewLabel: translationKeys.previewLabel,
                    previewModeTitle: translationKeys.previewModeTitle,
                    horizontal: translationKeys.horizontal,
                    vertical: translationKeys.vertical,
                    closePreview: translationKeys.closePreview,
                    preview: translationKeys.preview,
                    fullPreview: translationKeys.fullPreview,
                    splitView: translationKeys.splitView,
                }"
                @cancel="handleCancel"
                @input="handleContentInput"
                @set-layout-horizontal="setLayoutHorizontal"
                @set-layout-vertical="setLayoutVertical"
                @submit="handleSubmit"
                @toggle-full-preview="handleToggleFullPreview"
                @toggle-preview="handleTogglePreview"
            />

            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <input :id="`${fieldIdPrefix}-published`" v-model="form.is_published" type="checkbox" />
                    <label :for="`${fieldIdPrefix}-published`" class="text-sm">
                        {{ translationKeys.published }}
                    </label>
                </div>
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
