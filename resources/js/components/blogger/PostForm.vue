<script lang="ts" setup>
import FormCheckboxField from '@/components/blogger/FormCheckboxField.vue';
import FormSubmitActions from '@/components/blogger/FormSubmitActions.vue';
import MarkdownPreviewSection from '@/components/blogger/MarkdownPreviewSection.vue';
import PostFormField from '@/components/blogger/PostFormField.vue';
import { useMarkdownPreviewSection } from '@/composables/useMarkdownPreviewSection';
import type { AdminPostItem as PostItem } from '@/types/blog.types';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

interface Props {
    post?: PostItem;
    blogId?: number;
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
    idPrefix: 'post',
});

const emit = defineEmits<Emits>();

// Preview functionality using composable
const { isPreviewMode, isFullPreview, previewLayout, previewHtml, togglePreview, toggleFullPreview, setLayout, handleInput } =
    useMarkdownPreviewSection('markdown.preview');

// Form initialization
const form =
    props.form ||
    useForm({
        blog_id: props.blogId || props.post?.blog_id || 0,
        group_id: props.post?.group_id || 0,
        title: props.post?.title || '',
        seo_title: props.post?.seo_title || '',
        excerpt: props.post?.excerpt || '',
        content: props.post?.content || '',
        is_published: !!props.post?.is_published,
        visibility: props.post?.visibility || 'public',
    });

// Visibility computed properties
const createVisibilityComputed = (value: string) =>
    computed({
        get: () => form.visibility === value,
        set: (v: boolean) => {
            form.visibility = v ? value : 'public';
        },
    });

const isUnlisted = createVisibilityComputed('unlisted');
const isExtension = createVisibilityComputed('extension');

// Field ID prefix for consistent ID generation in template
const fieldIdPrefix = computed(() => props.idPrefix);

// Translation keys - organized by category
const translationKeys = computed(() => ({
    // Form labels
    title: props.isEdit ? t('blogger.post_form.title_label') : t('blogger.post_form.post_title_label'),
    titlePlaceholder: props.isEdit ? '' : t('blogger.post_form.title_placeholder'),
    seoTitle: t('blogger.post_form.seo_title_label'),
    seoTitlePlaceholder: t('blogger.post_form.seo_title_placeholder'),
    excerpt: t('blogger.post_form.excerpt_label'),
    excerptPlaceholder: props.isEdit ? '' : t('blogger.post_form.excerpt_placeholder'),
    content: t('blogger.post_form.content_label'),
    contentPlaceholder: props.isEdit ? '' : t('blogger.post_form.content_placeholder'),
    published: props.isEdit ? t('blogger.post_form.published_label') : t('blogger.post_form.publish_now_label'),

    // Buttons
    cancel: t('blogger.post_form.cancel_button'),
    create: t('blogger.post_form.create_post_button'),
    creating: t('blogger.post_form.creating_button'),
    save: t('blogger.post_form.save_post_button'),
    apply: t('blogger.post_form.apply_button'),
    saving: t('blogger.post_form.saving_button'),

    // Visibility
    unlisted: t('blogger.post_form.unlisted_label'),
    extension: t('blogger.post_form.extension_label'),

    // Preview
    preview: t('blogger.post_form.preview_button'),
    closePreview: t('blogger.post_form.close_button'),
    fullPreview: t('blogger.post_form.full_preview_button'),
    splitView: t('blogger.post_form.split_view_button'),
    toggleLayout: previewLayout.value === 'vertical' ? t('blogger.post_form.horizontal_button') : t('blogger.post_form.vertical_button'),
    exitPreview: t('blogger.post_form.exit_preview_button'),
    markdownLabel: t('blogger.post_form.markdown_label'),
    previewLabel: t('blogger.post_form.preview_label'),
    previewModeTitle: t('blogger.post_form.preview_mode_title'),
    characters: t('blogger.post_form.characters'),
}));

// Update form from post data
const updateFormFromPost = (post: PostItem) => {
    form.blog_id = post.blog_id;
    form.group_id = post.group_id || 0;
    form.title = post.title;
    form.seo_title = post.seo_title ?? '';
    form.excerpt = post.excerpt ?? '';
    form.content = post.content ?? '';
    form.is_published = post.is_published;
    form.visibility = post.visibility ?? 'public';
};

// Watchers for post and blogId props
if (!props.form) {
    watch(
        () => props.post,
        (newPost) => {
            if (newPost) updateFormFromPost(newPost);
        },
        { immediate: true },
    );

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

// Event handlers
const handleSubmit = () => emit('submit', form);

const handleApply = () =>
    form.patch(route('posts.update', props.post!.id), {
        preserveScroll: true,
        preserveState: true,
    });

const handleCancel = () => emit('cancel');

const handleTogglePreview = () => togglePreview(form.content);

const handleToggleFullPreview = () => toggleFullPreview(form.content);

const handleContentInput = () => handleInput(form.content);

const seoTitleClass = computed(() => {
    const length = form.seo_title?.length || 0;
    return length >= 50 && length <= 60 ? 'bg-secondary' : '';
});

const excerptClass = computed(() => {
    const length = form.excerpt?.length || 0;
    if (length > 160) return 'bg-destructive text-destructive-foreground';
    if (length > 120) return 'bg-constructive text-constructive-foreground';
    return '';
});
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
                :id="`${fieldIdPrefix}-seo-title`"
                v-model="form.seo_title"
                :error="form.errors?.seo_title"
                :input-class="seoTitleClass"
                :label="translationKeys.seoTitle"
                :placeholder="translationKeys.seoTitlePlaceholder"
                type="input"
            />

            <PostFormField
                :id="`${fieldIdPrefix}-excerpt`"
                v-model="form.excerpt"
                :error="form.errors?.excerpt"
                :hint="`${form.excerpt?.length || 0} ${translationKeys.characters}`"
                :input-class="excerptClass"
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
                :rows="props.isEdit ? 10 : 15"
                :translations="{
                    cancel: translationKeys.cancel,
                    create: translationKeys.create,
                    save: translationKeys.save,
                    exitPreview: translationKeys.exitPreview,
                    markdownLabel: translationKeys.markdownLabel,
                    previewLabel: translationKeys.previewLabel,
                    previewModeTitle: translationKeys.previewModeTitle,
                    toggleLayout: translationKeys.toggleLayout,
                    closePreview: translationKeys.closePreview,
                    preview: translationKeys.preview,
                    fullPreview: translationKeys.fullPreview,
                    splitView: translationKeys.splitView,
                }"
                @cancel="handleCancel"
                @input="handleContentInput"
                @submit="handleSubmit"
                @set-layout="setLayout"
                @toggle-full-preview="handleToggleFullPreview"
                @toggle-preview="handleTogglePreview"
            />

            <div class="flex flex-wrap items-center gap-4">
                <FormCheckboxField :id="`${fieldIdPrefix}-published`" v-model="form.is_published" :label="translationKeys.published" />

                <FormCheckboxField :id="`${fieldIdPrefix}-unlisted`" v-model="isUnlisted" :label="translationKeys.unlisted" />

                <FormCheckboxField :id="`${fieldIdPrefix}-extension`" v-model="isExtension" :label="translationKeys.extension" />
            </div>

            <FormSubmitActions
                :is-edit="props.isEdit"
                :is-processing="form.processing"
                :translations="{
                    cancel: translationKeys.cancel,
                    create: translationKeys.create,
                    save: translationKeys.save,
                    apply: translationKeys.apply,
                    creating: translationKeys.creating,
                    saving: translationKeys.saving,
                }"
                @apply="handleApply"
                @cancel="handleCancel"
                @submit="handleSubmit"
            />
        </form>
    </div>
</template>
