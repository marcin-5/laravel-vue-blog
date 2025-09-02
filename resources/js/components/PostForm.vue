<script lang="ts" setup>
import InputError from '@/components/InputError.vue';
import type { PostItem } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

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
                <label :for="`${props.idPrefix}-content-${props.post?.id || props.blogId}`" class="mb-1 block text-sm font-medium"> Content </label>
                <textarea
                    :id="`${props.idPrefix}-content-${props.post?.id || props.blogId}`"
                    v-model="form.content"
                    :placeholder="props.isEdit ? '' : 'Write your post...'"
                    :rows="props.isEdit ? 4 : 5"
                    class="block w-full rounded-md border px-3 py-2"
                />
                <InputError :message="form.errors.content" />
            </div>

            <div class="flex items-center gap-2">
                <input :id="`${props.idPrefix}-published-${props.post?.id || props.blogId}`" v-model="form.is_published" type="checkbox" />
                <label :for="`${props.idPrefix}-published-${props.post?.id || props.blogId}`" class="text-sm">
                    {{ props.isEdit ? 'Published' : 'Publish now' }}
                </label>
            </div>

            <div class="flex items-center gap-2">
                <button
                    :disabled="form.processing"
                    class="inline-flex cursor-pointer items-center rounded-md bg-primary px-4 py-2 text-primary-foreground disabled:cursor-not-allowed disabled:opacity-50"
                    type="submit"
                >
                    <span v-if="form.processing">
                        {{ props.isEdit ? 'Saving…' : 'Creating…' }}
                    </span>
                    <span v-else>
                        {{ props.isEdit ? 'Save Post' : 'Create Post' }}
                    </span>
                </button>
                <button class="cursor-pointer px-3 py-2" type="button" @click="handleCancel">Cancel</button>
            </div>
        </form>
    </div>
</template>
