<script lang="ts" setup>
import { computed, onMounted, ref, watch } from 'vue';

interface Props {
    blogId: number;
    idPrefix?: string;
    modelValue?: string[]; // selected tag slugs
}

const props = withDefaults(defineProps<Props>(), {
    idPrefix: 'post-tags',
    modelValue: () => [],
});

const emit = defineEmits<{ (e: 'update:modelValue', v: string[]): void }>();

const available = ref<Array<{ id: number; name: string; slug: string }>>([]);
const selected = ref<string[]>([]);

watch(
    () => props.modelValue,
    (v) => {
        selected.value = [...(v || [])];
    },
    { immediate: true },
);

function toggle(slug: string) {
    const idx = selected.value.indexOf(slug);
    if (idx === -1) {
        selected.value.push(slug);
    } else {
        selected.value.splice(idx, 1);
    }
    emit('update:modelValue', [...selected.value]);
}

const fieldId = computed(() => `${props.idPrefix}-selector`);

async function loadTags() {
    const res = await fetch(route('blogger.tags.index', { blog: props.blogId }), {
        headers: { Accept: 'application/json' },
    });
    available.value = await res.json();
}

onMounted(loadTags);
</script>

<template>
    <div class="space-y-4 rounded-lg border p-4">
        <label :for="fieldId" class="mb-2 block text-sm font-medium">{{ $t('blogger.post_form.tags_label') }}</label>
        <div :id="fieldId" class="flex flex-wrap gap-2">
            <button
                v-for="tag in available"
                :key="tag.id"
                :class="selected.includes(tag.slug) ? 'bg-constructive text-constructive-foreground' : 'bg-muted text-muted-foreground'"
                class="rounded px-2 py-1 text-sm"
                type="button"
                @click="toggle(tag.slug)"
            >
                {{ tag.name }}
            </button>
        </div>
        <div v-if="available" class="mt-1 text-sm text-muted-foreground">{{ $t('blogger.post_form.tags_hint') }}</div>
    </div>
</template>
