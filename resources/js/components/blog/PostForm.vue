<script lang="ts" setup>
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { useAppearance } from '@/composables/useAppearance';
import { ensureNamespace } from '@/i18n';
import type { PostItem } from '@/types';
import { MoonIcon, SunIcon } from '@heroicons/vue/24/outline';
import { useForm } from '@inertiajs/vue3';
import { useMediaQuery } from '@vueuse/core';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();
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

// Preview mode state
const isPreviewMode = ref(false);
const isFullPreview = ref(false);
const previewLayout = ref<'horizontal' | 'vertical'>('horizontal');
const previewHtml = ref('');

// Theme management
const { appearance, updateAppearance } = useAppearance();
const isSystemDark = useMediaQuery('(prefers-color-scheme: dark)');
const isDarkMode = computed(() => {
    if (appearance.value === 'system') {
        return isSystemDark.value;
    }
    return appearance.value === 'dark';
});

function toggleTheme() {
    updateAppearance(isDarkMode.value ? 'light' : 'dark');
}

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

// Preview functionality
const MARKDOWN_RENDER_ERROR_HTML = '<p class="text-red-500">Error rendering markdown</p>';

async function fetchMarkdownPreview(content: string): Promise<string> {
    const response = await fetch(route('posts.preview'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'text/html',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ content }),
    });

    if (!response.ok) {
        return MARKDOWN_RENDER_ERROR_HTML;
    }

    try {
        return await response.text();
    } catch (e) {
        return MARKDOWN_RENDER_ERROR_HTML;
    }
}

async function updatePreviewHtml() {
    previewHtml.value = await fetchMarkdownPreview(form.content);
}

watch(
    () => form.content,
    () => {
        if (isPreviewMode.value) updatePreviewHtml();
    },
);

function togglePreview() {
    isPreviewMode.value = !isPreviewMode.value;
    if (isPreviewMode.value) updatePreviewHtml();
}

function toggleFullPreview() {
    isFullPreview.value = !isFullPreview.value;
}

function setLayoutHorizontal() {
    previewLayout.value = 'horizontal';
}

function setLayoutVertical() {
    previewLayout.value = 'vertical';
}
</script>

<template>
    <div class="rounded-md border border-sidebar-border/70 p-4 dark:border-sidebar-border">
        <form class="space-y-4" @submit.prevent="handleSubmit">
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <input :id="`${props.idPrefix}-published`" v-model="form.is_published" type="checkbox" />
                    <label :for="`${props.idPrefix}-published`" class="text-sm">{{ $t('blogs.post_form.published_label') }}</label>
                </div>

                <div class="flex items-center gap-2">
                    <Button type="button" variant="outline" @click="toggleTheme">
                        <template v-if="isDarkMode">
                            <SunIcon class="mr-2 size-4" />
                            <span>Light</span>
                        </template>
                        <template v-else>
                            <MoonIcon class="mr-2 size-4" />
                            <span>Dark</span>
                        </template>
                    </Button>

                    <Button type="button" variant="outline" @click="togglePreview">
                        {{ isPreviewMode ? $t('blogs.post_form.hide_preview') : $t('blogs.post_form.show_preview') }}
                    </Button>
                </div>
            </div>

            <div>
                <label :for="`${props.idPrefix}-title`" class="mb-1 block text-sm font-medium">{{ $t('blogs.post_form.title_label') }}</label>
                <input
                    :id="`${props.idPrefix}-title`"
                    v-model="form.title"
                    :placeholder="props.isEdit ? '' : $t('blogs.post_form.title_placeholder')"
                    class="block w-full rounded-md border px-3 py-2"
                    required
                    type="text"
                />
                <InputError :message="form.errors.title" />
            </div>

            <div>
                <label :for="`${props.idPrefix}-excerpt`" class="mb-1 block text-sm font-medium">{{ $t('blogs.post_form.excerpt_label') }}</label>
                <textarea
                    :id="`${props.idPrefix}-excerpt`"
                    v-model="form.excerpt"
                    :placeholder="props.isEdit ? '' : $t('blogs.post_form.excerpt_placeholder')"
                    class="block w-full rounded-md border px-3 py-2"
                    rows="2"
                />
                <InputError :message="form.errors.excerpt" />
            </div>

            <div>
                <label :for="`${props.idPrefix}-content`" class="mb-1 block text-sm font-medium">{{ $t('blogs.post_form.content_label') }}</label>
                <textarea
                    :id="`${props.idPrefix}-content`"
                    v-model="form.content"
                    :placeholder="props.isEdit ? '' : $t('blogs.post_form.content_placeholder')"
                    class="block w-full rounded-md border px-3 py-2"
                    rows="12"
                />
                <InputError :message="form.errors.content" />
            </div>

            <div v-if="isPreviewMode" class="rounded-md border p-3">
                <div class="mb-2 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <Button :variant="previewLayout === 'horizontal' ? 'secondary' : 'outline'" type="button" @click="setLayoutHorizontal">
                            {{ $t('blogs.post_form.preview_horizontal') }}
                        </Button>
                        <Button :variant="previewLayout === 'vertical' ? 'secondary' : 'outline'" type="button" @click="setLayoutVertical">
                            {{ $t('blogs.post_form.preview_vertical') }}
                        </Button>
                    </div>
                    <Button :variant="isFullPreview ? 'secondary' : 'outline'" type="button" @click="toggleFullPreview">
                        {{ isFullPreview ? $t('blogs.post_form.exit_full_preview') : $t('blogs.post_form.full_preview') }}
                    </Button>
                </div>
                <div :class="isFullPreview ? 'fixed inset-4 z-50 rounded-md border bg-background p-4 shadow-lg' : ''">
                    <div :class="previewLayout === 'horizontal' ? 'grid grid-cols-2 gap-4' : 'space-y-4'">
                        <div>
                            <h3 class="mb-2 font-medium">{{ $t('blogs.post_form.preview_source') }}</h3>
                            <pre class="rounded bg-muted p-2 text-sm whitespace-pre-wrap">{{ form.content }}</pre>
                        </div>
                        <div>
                            <h3 class="mb-2 font-medium">{{ $t('blogs.post_form.preview_rendered') }}</h3>
                            <div class="prose dark:prose-invert max-w-none" v-html="previewHtml"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <Button :disabled="form.processing" type="submit" variant="constructive">
                    <span v-if="form.processing">
                        {{ props.isEdit ? $t('blogs.post_form.saving_button') : $t('blogs.post_form.creating_button') }}
                    </span>
                    <span v-else>
                        {{ props.isEdit ? $t('blogs.post_form.save_button') : $t('blogs.post_form.create_button') }}
                    </span>
                </Button>
                <Button type="button" variant="destructive" @click="handleCancel">{{ $t('blogs.post_form.cancel_button') }}</Button>
            </div>
        </form>
    </div>
</template>
