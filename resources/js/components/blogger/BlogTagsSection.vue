<script lang="ts" setup>
import { computed, onMounted, ref } from 'vue';
import PostFormField from '@/components/blogger/PostFormField.vue';

interface Props {
    blogId: number;
    idPrefix?: string;
}

const props = withDefaults(defineProps<Props>(), {
    idPrefix: 'blog-tags',
});

const tags = ref<Array<{ id: number; name: string; slug: string }>>([]);
const loading = ref(false);
const newTagName = ref('');
const errors = ref<Record<string, string>>({});

const fieldId = computed(() => `${props.idPrefix}-new`);

async function loadTags() {
    loading.value = true;
    try {
        const res = await fetch(route('blogger.tags.index', { blog: props.blogId }), {
            headers: { Accept: 'application/json' },
        });
        tags.value = await res.json();
    } finally {
        loading.value = false;
    }
}

async function createTag() {
    errors.value = {};
    if (!newTagName.value.trim()) {
        return;
    }
    const payload = { name: newTagName.value };
    const res = await fetch(route('blogger.tags.store', { blog: props.blogId }), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify(payload),
    });
    if (res.ok) {
        const tag = await res.json();
        tags.value.push(tag);
        newTagName.value = '';
    } else if (res.status === 422) {
        const data = await res.json();
        errors.value = data.errors || {};
    }
}

async function updateTag(idx: number, name: string) {
    const tag = tags.value[idx];
    if (!tag) return;
    const res = await fetch(route('blogger.tags.update', { blog: props.blogId, tag: tag.id }), {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ name }),
    });
    if (res.ok) {
        tags.value[idx] = await res.json();
    }
}

async function deleteTag(idx: number) {
    const tag = tags.value[idx];
    if (!tag) return;
    const res = await fetch(route('blogger.tags.destroy', { blog: props.blogId, tag: tag.id }), {
        method: 'DELETE',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });
    if (res.ok) {
        tags.value.splice(idx, 1);
    }
}

onMounted(loadTags);
</script>

<template>
    <div class="rounded-md border border-sidebar-border/70 p-4 dark:border-sidebar-border">
        <h3 class="mb-2 text-sm font-semibold">{{ $t('blogger.tags.section_title') }}</h3>
        <div class="mt-3 flex flex-wrap gap-2">
            <div v-for="(tag, idx) in tags" :key="tag.id" class="flex items-center gap-1 rounded bg-muted px-2 py-1">
                <input :value="tag.name" class="bg-transparent outline-none" @change="(e: any) => updateTag(idx, e.target.value)" />
                <button class="text-error" type="button" @click="deleteTag(idx)">×</button>
            </div>
        </div>
        <div v-if="errors.slug" class="mt-1 text-sm font-semibold text-error">{{ errors.slug }}</div>
        <div class="mt-3 flex items-end gap-2">
            <PostFormField :id="fieldId" v-model="newTagName" :error="errors.name" :label="$t('blogger.tags.add_label')" type="input" />
            <button :disabled="loading" class="btn btn-secondary h-10" type="button" @click="createTag">{{ $t('blogger.tags.add_button') }}</button>
        </div>
    </div>
</template>
