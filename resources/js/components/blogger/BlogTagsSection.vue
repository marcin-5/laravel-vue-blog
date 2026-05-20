<script lang="ts" setup>
import { onMounted, ref } from 'vue';
import { useHttp } from '@inertiajs/vue3';
import { Input } from '@/components/ui/input';
import { Check, Plus, X } from 'lucide-vue-next';

interface Tag {
    id: number;
    name: string;
    slug: string;
    originalName: string;
}

interface Props {
    blogId: number;
    idPrefix?: string;
}

const props = withDefaults(defineProps<Props>(), {
    idPrefix: 'blog-tags',
});

const tags = ref<Tag[]>([]);
const newTagName = ref('');
const http = useHttp({
    name: '',
    slug: '',
});

function sortTags() {
    tags.value.sort((a, b) => a.name.localeCompare(b.name));
}

async function loadTags() {
    try {
        const data = (await http.get(route('blogger.tags.index', { blog: props.blogId }))) as any[];
        tags.value = data.map((tag) => ({
            ...tag,
            originalName: tag.name,
        }));
        sortTags();
    } catch (error) {
        console.error('Failed to load tags', error);
    }
}

async function createTag() {
    if (!newTagName.value.trim()) {
        return;
    }
    try {
        http.name = newTagName.value;
        const tag = (await http.post(route('blogger.tags.store', { blog: props.blogId }))) as any;
        tags.value.push({ ...tag, originalName: tag.name });
        newTagName.value = '';
        sortTags();
    } catch {
        // Errors are automatically handled by http.errors
    }
}

async function updateTag(tag: Tag) {
    try {
        http.name = tag.name;
        const updated = (await http.patch(route('blogger.tags.update', { blog: props.blogId, tag: tag.slug }))) as any;
        tag.originalName = updated.name;
        tag.name = updated.name;
        tag.slug = updated.slug;
        sortTags();
    } catch {
        // Errors are automatically handled by http.errors
    }
}

async function deleteTag(tag: Tag) {
    try {
        await http.delete(route('blogger.tags.destroy', { blog: props.blogId, tag: tag.slug }));
        tags.value = tags.value.filter((t) => t.id !== tag.id);
    } catch (error) {
        console.error('Failed to delete tag', error);
    }
}

onMounted(loadTags);
</script>

<template>
    <div class="rounded-md border border-sidebar-border/70 p-4 dark:border-sidebar-border">
        <h3 class="mb-2 text-sm font-semibold">{{ $t('blogger.tags.section_title') }}</h3>
        <div class="mt-3 flex flex-wrap gap-2">
            <div v-for="tag in tags" :key="tag.id" class="flex items-center gap-1 rounded bg-muted px-2 py-1">
                <Input
                    v-model="tag.name"
                    class="h-7 w-auto min-w-32 border-none bg-transparent px-1 shadow-none focus-visible:ring-0"
                    @keyup.enter="tag.name !== tag.originalName && updateTag(tag)"
                />
                <button
                    :class="['text-success hover:text-success-hover', tag.name === tag.originalName && 'pointer-events-none invisible']"
                    type="button"
                    @click="updateTag(tag)"
                >
                    <Check class="h-4 w-4" />
                </button>
                <button class="text-error hover:text-error-hover" type="button" @click="deleteTag(tag)">
                    <X class="h-4 w-4" />
                </button>
            </div>

            <div
                class="flex items-center gap-1 rounded border border-dashed border-muted-foreground/30 bg-transparent px-2 py-1 focus-within:border-primary/50"
            >
                <Input
                    v-model="newTagName"
                    :placeholder="$t('blogger.tags.add_placeholder')"
                    class="h-7 w-auto min-w-32 border-none bg-transparent px-1 shadow-none focus-visible:ring-0"
                    @keyup.enter="createTag"
                />
                <button
                    :class="['text-primary hover:text-primary/80', !newTagName.trim() && 'pointer-events-none invisible']"
                    type="button"
                    @click="createTag"
                >
                    <Plus class="h-4 w-8" />
                </button>
            </div>
        </div>
        <div v-if="http.errors.name || http.errors.slug" class="mt-2 text-sm font-semibold text-error">
            {{ http.errors.name || http.errors.slug }}
        </div>
    </div>
</template>
