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
            Accept: 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({
            content: content,
        }),
    });

    if (!response.ok) {
        throw new Error('Failed to fetch markdown preview');
    }

    const data = await response.json();
    return data.html;
}

async function renderMarkdown() {
    if (!form.content) {
        previewHtml.value = '';
        return;
    }

    try {
        previewHtml.value = await fetchMarkdownPreview(form.content);
    } catch (error) {
        console.error('Failed to render markdown:', error);
        previewHtml.value = MARKDOWN_RENDER_ERROR_HTML;
    }
}

function togglePreview() {
    isPreviewMode.value = !isPreviewMode.value;
    if (isPreviewMode.value) {
        renderMarkdown();
    }
}

function toggleFullPreview() {
    isFullPreview.value = !isFullPreview.value;
    if (isFullPreview.value) {
        renderMarkdown();
    }
}

function togglePreviewLayout() {
    previewLayout.value = previewLayout.value === 'horizontal' ? 'vertical' : 'horizontal';
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
                <div v-if="isFullPreview" class="fixed inset-0 z-50 bg-background text-foreground">
                    <div class="sticky top-0 z-10 flex items-center justify-between border-b border-border bg-background p-4">
                        <h2 class="text-lg font-semibold">{{ $t('blogs.post_form.preview_mode_title') }}</h2>
                        <div class="flex gap-2">
                            <Button :disabled="form.processing" type="button" variant="constructive" @click="handleSubmit">
                                {{ props.isEdit ? $t('blogs.post_form.save_button') : $t('blogs.post_form.create_button') }}
                            </Button>
                            <Button type="button" variant="destructive" @click="handleCancel">{{ $t('blogs.post_form.cancel_button') }}</Button>
                            <Button type="button" variant="toggle" @click="togglePreviewLayout">
                                {{ previewLayout === 'horizontal' ? $t('blogs.post_form.horizontal_button') : $t('blogs.post_form.vertical_button') }}
                            </Button>
                            <Button class="flex items-center gap-2" type="button" variant="toggle" @click="toggleTheme">
                                <SunIcon v-if="isDarkMode" class="h-4 w-4" />
                                <MoonIcon v-else class="h-4 w-4" />
                            </Button>
                            <Button type="button" variant="exit" @click="toggleFullPreview">{{ $t('blogs.post_form.exit_preview_button') }}</Button>
                        </div>
                    </div>
                    <div class="h-full overflow-auto p-4">
                        <div :class="previewLayout === 'horizontal' ? 'flex h-full gap-4' : 'space-y-4'">
                            <div :class="previewLayout === 'horizontal' ? 'w-1/2' : ''">
                                <h3 class="mb-2 text-sm font-medium">{{ $t('blogs.post_form.markdown_label') }}</h3>
                                <textarea
                                    v-model="form.content"
                                    :placeholder="$t('blogs.post_form.markdown_placeholder')"
                                    class="h-96 w-full rounded border border-border bg-background px-3 py-2 font-mono text-sm text-foreground"
                                    @input="renderMarkdown"
                                />
                            </div>
                            <div :class="previewLayout === 'horizontal' ? 'w-1/2' : ''">
                                <h3 class="mb-2 text-sm font-medium">{{ $t('blogs.post_form.preview_label') }}</h3>
                                <div class="prose h-96 max-w-none overflow-auto rounded border border-border bg-muted p-4" v-html="previewHtml" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Normal Mode -->
                <div v-else>
                    <div v-if="isPreviewMode" :class="previewLayout === 'horizontal' ? 'flex gap-4' : 'space-y-4'">
                        <!-- Markdown Editor -->
                        <div :class="previewLayout === 'horizontal' ? 'w-1/2' : ''">
                            <textarea
                                :id="`${props.idPrefix}-content-${props.post?.id || props.blogId}`"
                                v-model="form.content"
                                :placeholder="props.isEdit ? '' : $t('blogs.post_form.content_placeholder')"
                                :rows="props.isEdit ? 8 : 10"
                                class="block w-full rounded-md border px-3 py-2"
                                @input="renderMarkdown"
                            />
                        </div>
                        <!-- Preview Pane -->
                        <div :class="previewLayout === 'horizontal' ? 'w-1/2' : ''">
                            <div
                                class="prose min-h-[200px] max-w-none overflow-auto rounded border border-border bg-muted p-4"
                                v-html="previewHtml"
                            />
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
                    <Button :variant="isPreviewMode ? 'exit' : 'toggle'" size="sm" type="button" @click="togglePreview">
                        {{ isPreviewMode ? $t('blogs.post_form.close_button') : $t('blogs.post_form.preview_button') }}
                    </Button>
                    <Button v-if="isPreviewMode" size="sm" type="button" variant="exit" @click="toggleFullPreview">
                        {{ isFullPreview ? $t('blogs.post_form.split_view_button') : $t('blogs.post_form.full_preview_button') }}
                    </Button>
                    <Button v-if="isPreviewMode && !isFullPreview" size="sm" type="button" variant="toggle" @click="togglePreviewLayout">
                        {{ previewLayout === 'horizontal' ? $t('blogs.post_form.horizontal_button') : $t('blogs.post_form.vertical_button') }}
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
