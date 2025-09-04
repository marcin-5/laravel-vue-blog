<script lang="ts" setup>
import CategorySelector from '@/components/CategorySelector.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import type { Blog, Category } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

interface Props {
    blog?: Blog;
    categories: Category[];
    isEdit?: boolean;
    idPrefix?: string;
    form?: any; // External form instance (create or edit)
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

// Use external form if provided, otherwise create internal form
const form =
    props.form ||
    useForm({
        name: props.blog?.name || '',
        description: props.blog?.description || (null as string | null),
        is_published: props.blog?.is_published || false,
        categories: (props.blog?.categories ?? []).map((c) => c.id) as number[],
    });

// Update form when blog prop changes (for edit mode) - only if using internal form
if (!props.form) {
    watch(
        () => props.blog,
        (newBlog) => {
            if (newBlog) {
                form.name = newBlog.name;
                form.description = newBlog.description;
                form.is_published = newBlog.is_published;
                form.categories = (newBlog.categories ?? []).map((c) => c.id);
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

function updateCategories(categoryIds: number[]) {
    form.categories = categoryIds;
}
</script>

<template>
    <div class="rounded-md border border-sidebar-border/70 p-4 dark:border-sidebar-border">
        <form class="space-y-4" @submit.prevent="handleSubmit">
            <div>
                <label :for="`${props.idPrefix}-name`" class="mb-1 block text-sm font-medium">Name</label>
                <input
                    :id="`${props.idPrefix}-name`"
                    v-model="form.name"
                    :placeholder="props.isEdit ? '' : 'My Awesome Blog'"
                    class="block w-full rounded-md border px-3 py-2"
                    required
                    type="text"
                />
                <InputError :message="form.errors.name" />
            </div>

            <div>
                <label :for="`${props.idPrefix}-description`" class="mb-1 block text-sm font-medium">Description</label>
                <textarea
                    :id="`${props.idPrefix}-description`"
                    v-model="form.description"
                    :placeholder="props.isEdit ? '' : 'What\'s this blog about?'"
                    class="block w-full rounded-md border px-3 py-2"
                    rows="3"
                />
                <InputError :message="form.errors.description" />
            </div>

            <!-- Published checkbox (only for edit mode) -->
            <div v-if="props.isEdit && props.blog" class="flex items-center gap-2">
                <input :id="`${props.idPrefix}-published`" v-model="form.is_published" type="checkbox" />
                <label :for="`${props.idPrefix}-published`" class="text-sm">Published</label>
                <span class="text-xs text-muted-foreground">/{{ props.blog.slug }}</span>
            </div>
            <InputError v-if="props.isEdit" :message="form.errors.is_published" />

            <CategorySelector
                :categories="props.categories"
                :id-prefix="`${props.idPrefix}-cat`"
                :selected-categories="form.categories"
                @update:selected-categories="updateCategories"
            />
            <InputError :message="form.errors.categories" />

            <div class="flex items-center gap-2">
                <Button :disabled="form.processing" type="submit" variant="constructive">
                    <span v-if="form.processing">
                        {{ props.isEdit ? 'Saving…' : 'Creating…' }}
                    </span>
                    <span v-else>
                        {{ props.isEdit ? 'Save' : 'Create' }}
                    </span>
                </Button>
                <Button type="button" variant="destructive" @click="handleCancel"> Cancel </Button>
            </div>
        </form>
    </div>
</template>
