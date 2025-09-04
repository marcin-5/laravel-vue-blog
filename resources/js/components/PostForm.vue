<script lang="ts" setup>
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { useAppearance } from '@/composables/useAppearance';
import type { PostItem } from '@/types';
import { MoonIcon, SunIcon } from '@heroicons/vue/24/outline';
import { useForm } from '@inertiajs/vue3';
import { onMounted, ref, watch } from 'vue';

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
const isDarkMode = ref(false);

// Initialize theme state
onMounted(() => {
    updateCurrentTheme();
});

function updateCurrentTheme() {
    if (appearance.value === 'system') {
        isDarkMode.value = window.matchMedia('(prefers-color-scheme: dark)').matches;
    } else {
        isDarkMode.value = appearance.value === 'dark';
    }
}

function toggleTheme() {
    const newTheme = isDarkMode.value ? 'light' : 'dark';
    updateAppearance(newTheme);
    isDarkMode.value = !isDarkMode.value;
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

// Update form when post prop changes (for edit mode) - only if using internal form
if (!props.form) {
    watch(
        () => props.post,
        (newPost) => {
            if (newPost) {
                form.blog_id = newPost.blog_id;
                form.title = newPost.title;
                form.excerpt = newPost.excerpt ?? '';
                form.content = newPost.content ?? '';
                form.is_published = !!newPost.is_published;
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
async function renderMarkdown() {
    if (!form.content) {
        previewHtml.value = '';
        return;
    }

    try {
        const response = await fetch(route('posts.preview'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                content: form.content,
            }),
        });

        if (response.ok) {
            const data = await response.json();
            previewHtml.value = data.html;
        } else {
            previewHtml.value = '<p class="text-red-500">Error rendering markdown</p>';
        }
    } catch (error) {
        console.error('Failed to render markdown:', error);
        previewHtml.value = '<p class="text-red-500">Error rendering markdown</p>';
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
                    {{ props.isEdit ? 'Title' : 'Post title' }}
                </label>
                <input
                    :id="`${props.idPrefix}-title-${props.post?.id || props.blogId}`"
                    v-model="form.title"
                    :placeholder="props.isEdit ? '' : 'My first post'"
                    class="block w-full rounded-md border px-3 py-2"
                    required
                    type="text"
                />
                <InputError :message="form.errors.title" />
            </div>

            <div>
                <label :for="`${props.idPrefix}-excerpt-${props.post?.id || props.blogId}`" class="mb-1 block text-sm font-medium"> Excerpt </label>
                <textarea
                    :id="`${props.idPrefix}-excerpt-${props.post?.id || props.blogId}`"
                    v-model="form.excerpt"
                    :placeholder="props.isEdit ? '' : 'Short summary'"
                    class="block w-full rounded-md border px-3 py-2"
                    rows="2"
                />
                <InputError :message="form.errors.excerpt" />
            </div>

            <div>
                <div class="mb-1">
                    <label :for="`${props.idPrefix}-content-${props.post?.id || props.blogId}`" class="block text-sm font-medium"> Content </label>
                </div>

                <!-- Full Preview Mode -->
                <div v-if="isFullPreview" class="fixed inset-0 z-50 bg-background text-foreground">
                    <div class="sticky top-0 z-10 flex items-center justify-between border-b border-border bg-background p-4">
                        <h2 class="text-lg font-semibold">Preview Mode</h2>
                        <div class="flex gap-2">
                            <Button :disabled="form.processing" type="button" variant="constructive" @click="handleSubmit">
                                {{ props.isEdit ? 'Save' : 'Create' }}
                            </Button>
                            <Button type="button" variant="destructive" @click="handleCancel"> Cancel </Button>
                            <Button type="button" variant="toggle" @click="togglePreviewLayout">
                                {{ previewLayout === 'horizontal' ? 'Horizontal' : 'Vertical' }}
                            </Button>
                            <Button class="flex items-center gap-2" type="button" variant="toggle" @click="toggleTheme">
                                <SunIcon v-if="isDarkMode" class="h-4 w-4" />
                                <MoonIcon v-else class="h-4 w-4" />
                            </Button>
                            <Button type="button" variant="exit" @click="toggleFullPreview"> Exit Preview </Button>
                        </div>
                    </div>
                    <div class="h-full overflow-auto p-4">
                        <div :class="previewLayout === 'horizontal' ? 'flex h-full gap-4' : 'space-y-4'">
                            <div :class="previewLayout === 'horizontal' ? 'w-1/2' : ''">
                                <h3 class="mb-2 text-sm font-medium">Markdown</h3>
                                <textarea
                                    v-model="form.content"
                                    class="h-96 w-full rounded border border-border bg-background px-3 py-2 font-mono text-sm text-foreground"
                                    placeholder="Write your markdown here..."
                                    @input="renderMarkdown"
                                />
                            </div>
                            <div :class="previewLayout === 'horizontal' ? 'w-1/2' : ''">
                                <h3 class="mb-2 text-sm font-medium">Preview</h3>
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
                                :placeholder="props.isEdit ? '' : 'Write your post...'"
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
                            :placeholder="props.isEdit ? '' : 'Write your post...'"
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
                        {{ props.isEdit ? 'Published' : 'Publish now' }}
                    </label>
                </div>
                <div class="flex gap-2">
                    <Button :variant="isPreviewMode ? 'exit' : 'toggle'" size="sm" type="button" @click="togglePreview">
                        {{ isPreviewMode ? 'Close' : 'Preview' }}
                    </Button>
                    <Button v-if="isPreviewMode" size="sm" type="button" variant="exit" @click="toggleFullPreview">
                        {{ isFullPreview ? 'Split View' : 'Full Preview' }}
                    </Button>
                    <Button v-if="isPreviewMode && !isFullPreview" size="sm" type="button" variant="toggle" @click="togglePreviewLayout">
                        {{ previewLayout === 'horizontal' ? 'Horizontal' : 'Vertical' }}
                    </Button>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <Button :disabled="form.processing" type="submit" variant="constructive">
                    <span v-if="form.processing">
                        {{ props.isEdit ? 'Saving…' : 'Creating…' }}
                    </span>
                    <span v-else>
                        {{ props.isEdit ? 'Save Post' : 'Create Post' }}
                    </span>
                </Button>
                <Button type="button" variant="destructive" @click="handleCancel">Cancel</Button>
            </div>
        </form>
    </div>
</template>
