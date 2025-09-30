<script lang="ts" setup>
import FullScreenPreview from '@/components/FullScreenPreview.vue';
import InputError from '@/components/InputError.vue';
import MarkdownPreview from '@/components/MarkdownPreview.vue';
import { Button } from '@/components/ui/button';
import { useMarkdownPreview } from '@/composables/useMarkdownPreview';
import { ensureNamespace } from '@/i18n';
import type { PostItem } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { locale } = useI18n();
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
} = useMarkdownPreview('posts.preview');

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

const updateFormFromPost = (post: PostItem) => {
    form.blog_id = post.blog_id;
    form.title = post.title;
    form.excerpt = post.excerpt ?? '';
    form.content = post.content ?? '';
    form.is_published = !!post.is_published;
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
    renderMarkdown(form.content);
}
</script>

<template>
    <div class="mt-4 border-t pt-4">
        <form class="space-y-4" @submit.prevent="handleSubmit">
            <div>
                <label :for="`${props.idPrefix}-title-${props.post?.id || props.blogId}`" class="mb-1 block text-sm font-medium">
                    {{ props.isEdit ? $t('blogs.post_form.title_label') : $t('blogs.post_form.post_title_label') }}
                </label>
                <input
                    :id="`${props.idPrefix}-title-${props.post?.id || props.blogId}`"
                    v-model="form.title"
                    :placeholder="props.isEdit ? '' : $t('blogs.post_form.title_placeholder')"
                    class="block w-full rounded-md border px-3 py-2"
                    required
                    type="text"
                />
                <InputError :message="form.errors.title" />
            </div>

            <div>
                <label :for="`${props.idPrefix}-excerpt-${props.post?.id || props.blogId}`" class="mb-1 block text-sm font-medium">{{
                    $t('blogs.post_form.excerpt_label')
                }}</label>
                <textarea
                    :id="`${props.idPrefix}-excerpt-${props.post?.id || props.blogId}`"
                    v-model="form.excerpt"
                    :placeholder="props.isEdit ? '' : $t('blogs.post_form.excerpt_placeholder')"
                    class="block w-full rounded-md border px-3 py-2"
                    rows="2"
                />
                <InputError :message="form.errors.excerpt" />
            </div>

            <div>
                <div class="mb-1">
                    <label :for="`${props.idPrefix}-content-${props.post?.id || props.blogId}`" class="block text-sm font-medium">{{
                        $t('blogs.post_form.content_label')
                    }}</label>
                </div>

                <!-- Full Preview Mode -->
                <FullScreenPreview
                    v-if="isFullPreview"
                    v-model:content="form.content"
                    :cancel-button-label="$t('blogs.post_form.cancel_button')"
                    :create-button-label="$t('blogs.post_form.create_button')"
                    :exit-preview-button-label="$t('blogs.post_form.exit_preview_button')"
                    :horizontal-button-label="$t('blogs.post_form.horizontal_button')"
                    :is-edit="props.isEdit"
                    :is-processing="form.processing"
                    :markdown-label="$t('blogs.post_form.markdown_label')"
                    :markdown-placeholder="$t('blogs.post_form.markdown_placeholder')"
                    :preview-html="previewHtml"
                    :preview-label="$t('blogs.post_form.preview_label')"
                    :preview-layout="previewLayout"
                    :preview-mode-title-label="$t('blogs.post_form.preview_mode_title')"
                    :save-button-label="$t('blogs.post_form.save_button')"
                    :vertical-button-label="$t('blogs.post_form.vertical_button')"
                    @cancel="handleCancel"
                    @exit="handleToggleFullPreview"
                    @input="handleContentInput"
                    @layout="(val) => (val === 'horizontal' ? setLayoutHorizontal() : setLayoutVertical())"
                    @save="handleSubmit"
                />

                <!-- Normal Mode -->
                <div v-else>
                    <div v-if="isPreviewMode" :class="previewLayout === 'vertical' ? 'flex gap-4' : 'space-y-4'">
                        <!-- Markdown Editor -->
                        <div :class="previewLayout === 'vertical' ? 'w-1/2' : ''">
                            <textarea
                                :id="`${props.idPrefix}-content-${props.post?.id || props.blogId}`"
                                v-model="form.content"
                                :placeholder="props.isEdit ? '' : $t('blogs.post_form.content_placeholder')"
                                :rows="props.isEdit ? 8 : 10"
                                class="block w-full rounded-md border px-3 py-2"
                                @input="handleContentInput"
                            />
                        </div>
                        <!-- Preview Pane -->
                        <div :class="previewLayout === 'vertical' ? 'w-1/2' : ''">
                            <MarkdownPreview :html="previewHtml" class="min-h-[200px]" />
                        </div>
                    </div>
                    <div v-else>
                        <textarea
                            :id="`${props.idPrefix}-content-${props.post?.id || props.blogId}`"
                            v-model="form.content"
                            :placeholder="props.isEdit ? '' : $t('blogs.post_form.content_placeholder')"
                            :rows="props.isEdit ? 4 : 5"
                            class="block w-full rounded-md border px-3 py-2"
                        />
                    </div>
                </div>
                <InputError :message="form.errors.content" />
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <input :id="`${props.idPrefix}-published-${props.post?.id || props.blogId}`" v-model="form.is_published" type="checkbox" />
                    <label :for="`${props.idPrefix}-published-${props.post?.id || props.blogId}`" class="text-sm">
                        {{ props.isEdit ? $t('blogs.post_form.published_label') : $t('blogs.post_form.publish_now_label') }}
                    </label>
                </div>
                <div class="flex gap-2">
                    <Button :variant="isPreviewMode ? 'exit' : 'toggle'" size="sm" type="button" @click="handleTogglePreview">
                        {{ isPreviewMode ? $t('blogs.post_form.close_button') : $t('blogs.post_form.preview_button') }}
                    </Button>
                    <Button v-if="isPreviewMode" size="sm" type="button" variant="exit" @click="handleToggleFullPreview">
                        {{ isFullPreview ? $t('blogs.post_form.split_view_button') : $t('blogs.post_form.full_preview_button') }}
                    </Button>
                    <Button v-if="isPreviewMode && !isFullPreview" size="sm" type="button" variant="toggle" @click="setLayoutHorizontal">
                        {{ $t('blogs.post_form.horizontal_button') }}
                    </Button>
                    <Button v-if="isPreviewMode && !isFullPreview" size="sm" type="button" variant="toggle" @click="setLayoutVertical">
                        {{ $t('blogs.post_form.vertical_button') }}
                    </Button>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <Button :disabled="form.processing" type="submit" variant="constructive">
                    <span v-if="form.processing">
                        {{ props.isEdit ? $t('blogs.post_form.saving_button') : $t('blogs.post_form.creating_button') }}
                    </span>
                    <span v-else>
                        {{ props.isEdit ? $t('blogs.post_form.save_post_button') : $t('blogs.post_form.create_post_button') }}
                    </span>
                </Button>
                <Button type="button" variant="destructive" @click="handleCancel">{{ $t('blogs.post_form.cancel_button') }}</Button>
            </div>
        </form>
    </div>
</template>
