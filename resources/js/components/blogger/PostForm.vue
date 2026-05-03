<script lang="ts" setup>
import FormCheckboxField from '@/components/blogger/FormCheckboxField.vue';
import FormSubmitActions from '@/components/blogger/FormSubmitActions.vue';
import MarkdownPreviewSection from '@/components/blogger/MarkdownPreviewSection.vue';
import PostExternalLinksSection from '@/components/blogger/PostExternalLinksSection.vue';
import PostFormField from '@/components/blogger/PostFormField.vue';
import PostRelatedPostsSection from '@/components/blogger/PostRelatedPostsSection.vue';
import { useMarkdownPreviewSection } from '@/composables/useMarkdownPreviewSection';
import type { AdminPostItem as PostItem, ExternalLinkItem, RelatedPostItem } from '@/types/blog.types';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

interface Props {
    post?: PostItem;
    blogId?: number;
    groupId?: number;
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

// Preview functionality for content
const contentPreview = useMarkdownPreviewSection('markdown.preview');

// Preview functionality for summary
const summaryPreview = useMarkdownPreviewSection('markdown.preview');

// Form initialization
const form =
    props.form ||
    useForm({
        blog_id: props.blogId || props.post?.blog_id || 0,
        group_id: props.post?.group_id || 0,
        title: props.post?.title || '',
        seo_title: props.post?.seo_title || '',
        excerpt: props.post?.excerpt || '',
        summary: props.post?.summary || '',
        content: props.post?.content || '',
        is_published: !!props.post?.is_published,
        visibility: props.post?.visibility || 'public',
        related_posts: (props.post?.related_posts || []) as RelatedPostItem[],
        external_links: (props.post?.external_links || []) as ExternalLinkItem[],
    });

// Ensure summary, related_posts and external_links are initialized if props.form was provided without them
if (form.summary === undefined) {
    form.summary = props.post?.summary || '';
}
if (form.related_posts === undefined) {
    form.related_posts = (props.post?.related_posts || []) as RelatedPostItem[];
}
if (form.external_links === undefined) {
    form.external_links = (props.post?.external_links || []) as ExternalLinkItem[];
}

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
    summary: t('blogger.post_form.summary_label'),
    summaryPlaceholder: props.isEdit ? '' : t('blogger.post_form.summary_placeholder'),
    content: t('blogger.post_form.content_label'),
    contentPlaceholder: props.isEdit ? '' : t('blogger.post_form.content_placeholder'),
    previewToggleLayout: (layout: string) =>
        layout === 'vertical' ? t('blogger.post_form.horizontal_button') : t('blogger.post_form.vertical_button'),
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
    exitPreview: t('blogger.post_form.exit_preview_button'),
    markdownLabel: t('blogger.post_form.markdown_label'),
    previewLabel: t('blogger.post_form.preview_label'),
    previewModeTitle: t('blogger.post_form.preview_mode_title'),
    characters: t('blogger.post_form.characters'),
}));

const relatedPostsTranslations = computed(() => ({
    label: t('blogger.post_form.related_posts_label'),
    addItem: t('blogger.post_form.add_related_post'),
    blogId: t('blogger.post_form.related_post_blog'),
    postId: t('blogger.post_form.related_post_id'),
    reason: t('blogger.post_form.related_post_reason'),
}));

const externalLinksTranslations = computed(() => ({
    label: t('blogger.post_form.external_links_label'),
    addItem: t('blogger.post_form.add_external_link'),
    updateItem: t('blogger.post_form.update_external_link'),
    title: t('blogger.post_form.external_link_title'),
    url: t('blogger.post_form.external_link_url'),
    description: t('blogger.post_form.external_link_description'),
    reason: t('blogger.post_form.external_link_reason'),
}));

const externalLinksErrors = computed(() => {
    if (!form.errors) return {};

    const errors: Record<string, string> = {};
    Object.keys(form.errors).forEach((key) => {
        if (key.startsWith('external_links.')) {
            errors[key] = form.errors[key];
        }
    });
    return errors;
});

// Update form from post data
const updateFormFromPost = (post: PostItem) => {
    form.blog_id = post.blog_id;
    form.group_id = post.group_id || 0;
    form.title = post.title;
    form.seo_title = post.seo_title ?? '';
    form.excerpt = post.excerpt ?? '';
    form.summary = post.summary ?? '';
    form.content = post.content ?? '';
    form.is_published = post.is_published;
    form.visibility = post.visibility ?? 'public';
    form.related_posts = (post.related_posts || []) as RelatedPostItem[];
    form.external_links = (post.external_links || []) as ExternalLinkItem[];
};

const addRelatedPost = (item: { blog_id: number; related_post_id: number; reason: string }) => {
    form.related_posts.push({
        blog_id: item.blog_id,
        related_post_id: item.related_post_id,
        reason: item.reason,
        display_order: form.related_posts.length,
    });
};

const removeRelatedPost = (index: number) => {
    form.related_posts.splice(index, 1);
};

const addExternalLink = (item: { title: string; url: string; description: string; reason: string }) => {
    form.external_links.push({
        title: item.title,
        url: item.url,
        description: item.description,
        reason: item.reason,
        display_order: form.external_links.length,
    });
};

const removeExternalLink = (index: number) => {
    form.external_links.splice(index, 1);
};

const updateExternalLink = (index: number, item: { title: string; url: string; description: string; reason: string }) => {
    form.external_links[index] = {
        ...form.external_links[index],
        ...item,
    };
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

// Watcher for isExtension to clear fields when true
watch(isExtension, (newValue) => {
    if (newValue) {
        form.seo_title = '';
        form.excerpt = '';
        form.summary = '';
        form.related_posts = [];
        form.external_links = [];
    }
});

// Event handlers
const handleSubmit = () => emit('submit', form);

const handleApply = () =>
    form.patch(route('posts.update', props.post!.id), {
        preserveScroll: true,
        preserveState: true,
    });

const handleCancel = () => emit('cancel');

const handleToggleContentPreview = () => contentPreview.togglePreview(form.content);

const handleToggleContentFullPreview = () => contentPreview.toggleFullPreview(form.content);

const handleContentInput = () => contentPreview.handleInput(form.content);

const handleToggleSummaryPreview = () => summaryPreview.togglePreview(form.summary);

const handleToggleSummaryFullPreview = () => summaryPreview.toggleFullPreview(form.summary);

const handleSummaryInput = () => summaryPreview.handleInput(form.summary);

// helpers for SEO data length validation
const getRangeClass = computed(() => (value: string | null, from: number, to: number): string => {
    const length = value?.length || 0;
    return length >= from && length <= to ? 'bg-secondary' : '';
});

const getThresholdClass = computed(() => (value: string | null, threshold1: number, threshold2: number): string => {
    const length = value?.length || 0;
    if (length > threshold2) return 'bg-destructive text-destructive-foreground';
    if (length > threshold1) return 'bg-constructive text-constructive-foreground';
    return '';
});
</script>

<template>
    <div class="mt-4 border-t pt-4">
        <form class="space-y-4" @submit.prevent="handleSubmit">
            <div class="flex flex-wrap items-center gap-4">
                <FormCheckboxField :id="`${fieldIdPrefix}-published`" v-model="form.is_published" :label="translationKeys.published" />

                <FormCheckboxField :id="`${fieldIdPrefix}-unlisted`" v-model="isUnlisted" :label="translationKeys.unlisted" />

                <FormCheckboxField :id="`${fieldIdPrefix}-extension`" v-model="isExtension" :label="translationKeys.extension" />
            </div>

            <PostFormField
                :id="`${fieldIdPrefix}-title`"
                v-model="form.title"
                :error="form.errors?.title"
                :hint="`${form.title?.length || 0} ${translationKeys.characters}`"
                :input-class="getRangeClass(form.title, 50, 60)"
                :label="translationKeys.title"
                :placeholder="translationKeys.titlePlaceholder"
                required
                type="input"
            />

            <template v-if="!isExtension">
                <PostFormField
                    v-if="!props.groupId && !form.group_id"
                    :id="`${fieldIdPrefix}-seo-title`"
                    v-model="form.seo_title"
                    :error="form.errors?.seo_title"
                    :hint="`${form.seo_title?.length || 0} ${translationKeys.characters}`"
                    :input-class="getRangeClass(form.seo_title, 50, 60)"
                    :label="translationKeys.seoTitle"
                    :placeholder="translationKeys.seoTitlePlaceholder"
                    type="input"
                />

                <PostFormField
                    :id="`${fieldIdPrefix}-excerpt`"
                    v-model="form.excerpt"
                    :error="form.errors?.excerpt"
                    :hint="`${form.excerpt?.length || 0} ${translationKeys.characters}`"
                    :input-class="getThresholdClass(form.excerpt, 120, 160)"
                    :label="translationKeys.excerpt"
                    :placeholder="translationKeys.excerptPlaceholder"
                    :rows="2"
                    type="textarea"
                />
            </template>

            <MarkdownPreviewSection
                :id="`${fieldIdPrefix}-content`"
                v-model="form.content"
                :error="form.errors.content"
                :hint="`${form.content?.length || 0} ${translationKeys.characters}`"
                :is-edit="props.isEdit"
                :is-full-preview="contentPreview.isFullPreview.value"
                :is-preview-mode="contentPreview.isPreviewMode.value"
                :is-processing="form.processing"
                :label="translationKeys.content"
                :placeholder="translationKeys.contentPlaceholder"
                :preview-html="contentPreview.previewHtml.value"
                :preview-layout="contentPreview.previewLayout.value"
                :rows="props.isEdit ? 10 : 15"
                :translations="{
                    cancel: translationKeys.cancel,
                    create: translationKeys.create,
                    save: translationKeys.save,
                    exitPreview: translationKeys.exitPreview,
                    markdownLabel: translationKeys.markdownLabel,
                    previewLabel: translationKeys.previewLabel,
                    previewModeTitle: translationKeys.previewModeTitle,
                    toggleLayout: translationKeys.previewToggleLayout(contentPreview.previewLayout.value),
                    closePreview: translationKeys.closePreview,
                    preview: translationKeys.preview,
                    fullPreview: translationKeys.fullPreview,
                    splitView: translationKeys.splitView,
                }"
                @cancel="handleCancel"
                @input="handleContentInput"
                @submit="handleSubmit"
                @set-layout="contentPreview.setLayout"
                @toggle-full-preview="handleToggleContentFullPreview"
                @toggle-preview="handleToggleContentPreview"
            />

            <MarkdownPreviewSection
                v-if="!isExtension"
                :id="`${fieldIdPrefix}-summary`"
                v-model="form.summary"
                :error="form.errors.summary"
                :is-edit="props.isEdit"
                :is-full-preview="summaryPreview.isFullPreview.value"
                :is-preview-mode="summaryPreview.isPreviewMode.value"
                :is-processing="form.processing"
                :label="translationKeys.summary"
                :placeholder="translationKeys.summaryPlaceholder"
                :preview-html="summaryPreview.previewHtml.value"
                :preview-layout="summaryPreview.previewLayout.value"
                :rows="props.isEdit ? 4 : 6"
                :translations="{
                    cancel: translationKeys.cancel,
                    create: translationKeys.create,
                    save: translationKeys.save,
                    exitPreview: translationKeys.exitPreview,
                    markdownLabel: translationKeys.markdownLabel,
                    previewLabel: translationKeys.previewLabel,
                    previewModeTitle: translationKeys.previewModeTitle,
                    toggleLayout: translationKeys.previewToggleLayout(summaryPreview.previewLayout.value),
                    closePreview: translationKeys.closePreview,
                    preview: translationKeys.preview,
                    fullPreview: translationKeys.fullPreview,
                    splitView: translationKeys.splitView,
                }"
                @cancel="handleCancel"
                @input="handleSummaryInput"
                @submit="handleSubmit"
                @set-layout="summaryPreview.setLayout"
                @toggle-full-preview="handleToggleSummaryFullPreview"
                @toggle-preview="handleToggleSummaryPreview"
            />

            <template v-if="!isExtension">
                <PostRelatedPostsSection
                    :id-prefix="fieldIdPrefix"
                    :items="form.related_posts"
                    :translations="relatedPostsTranslations"
                    @remove="removeRelatedPost"
                    @add-item="addRelatedPost"
                />

                <PostExternalLinksSection
                    :errors="externalLinksErrors"
                    :id-prefix="fieldIdPrefix"
                    :items="form.external_links"
                    :translations="externalLinksTranslations"
                    @remove="removeExternalLink"
                    @add-item="addExternalLink"
                    @update-item="updateExternalLink"
                />
            </template>

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
