<script lang="ts" setup>
import { stripCutMarkers } from '@/types/blog';
import type { Blog } from '@/types/blog.types';
import '@fontsource/noto-serif';

defineProps<{
    blog: Blog;
    displayedMotto: string | null;
}>();

const NOTO_SERIF_FONT = "'Noto Serif', serif";
</script>

<template>
    <header class="mb-4">
        <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-400">
            {{ blog.name }}
        </h1>
        <p v-if="displayedMotto" :style="{ fontFamily: NOTO_SERIF_FONT }" class="mt-2 text-gray-800 italic dark:text-gray-200">
            {{ displayedMotto }}
        </p>
        <div v-if="blog.descriptionHtml" class="prose prose-slate dark:prose-invert max-w-none" v-html="stripCutMarkers(blog.descriptionHtml)" />
    </header>
</template>
