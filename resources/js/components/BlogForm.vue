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
        locale: (props.blog?.locale as string) || 'en',
        sidebar: (props.blog?.sidebar as number) ?? 0,
        page_size: (props.blog?.page_size as number) ?? 10,
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
                form.locale = (newBlog.locale as string) || 'en';
                form.sidebar = (newBlog.sidebar as number) ?? 0;
                form.page_size = (newBlog.page_size as number) ?? 10;
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

            <!-- Published checkbox + Locale selector (only for edit mode) -->
            <div v-if="props.isEdit && props.blog" class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2">
                    <input :id="`${props.idPrefix}-published`" v-model="form.is_published" type="checkbox" />
                    <label :for="`${props.idPrefix}-published`" class="text-sm">Published</label>
                    <span class="text-xs text-muted-foreground">/{{ props.blog.slug }}</span>
                </div>
                <div class="ml-auto flex items-center gap-2">
                    <label :for="`${props.idPrefix}-locale`" class="text-sm">Locale</label>
                    <select :id="`${props.idPrefix}-locale`" v-model="form.locale" class="rounded border px-2 py-1 text-sm">
                        <option v-for="loc in ['en','pl']" :key="`loc-${loc}`" :value="loc">{{ loc.toUpperCase() }}</option>
                    </select>
                </div>
            </div>
            <div v-if="props.isEdit" class="flex items-center gap-4">
                <InputError :message="form.errors.is_published" />
                <InputError :message="form.errors.locale" />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label :for="`${props.idPrefix}-sidebar`" class="mb-1 block text-sm font-medium">Sidebar position/width</label>
                    <input
                        :id="`${props.idPrefix}-sidebar`"
                        v-model.number="form.sidebar"
                        class="block w-full rounded-md border px-3 py-2"
                        min="-50"
                        max="50"
                        step="1"
                        type="number"
                    />
                    <p class="mt-1 text-xs text-muted-foreground">-50..-1 = left (% width), 0 = none, 1..50 = right (% width)</p>
                    <InputError :message="form.errors.sidebar" />
                </div>
                <div>
                    <label :for="`${props.idPrefix}-page_size`" class="mb-1 block text-sm font-medium">Posts per page</label>
                    <input
                        :id="`${props.idPrefix}-page_size`"
                        v-model.number="form.page_size"
                        class="block w-full rounded-md border px-3 py-2"
                        min="1"
                        max="100"
                        step="1"
                        type="number"
                    />
                    <InputError :message="form.errors.page_size" />
                </div>
            </div>

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
