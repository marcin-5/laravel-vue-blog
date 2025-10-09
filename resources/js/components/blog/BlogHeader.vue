<script lang="ts" setup>
import '@fontsource/noto-serif';

interface Blog {
    id: number;
    name: string;
    slug: string;
    descriptionHtml?: string | null;
    motto?: string | null;
}

defineProps<{
    blog: Blog;
    displayedMotto: string | null;
}>();
</script>

<template>
    <header class="mb-4">
        <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-400">{{ blog.name }}</h1>
        <p v-if="displayedMotto" class="mt-2 text-gray-800 italic dark:text-gray-200" style="font-family: 'Noto Serif', serif">
            {{ displayedMotto }}
        </p>
        <div v-if="blog.descriptionHtml" class="prose prose-slate dark:prose-invert max-w-none" v-html="blog.descriptionHtml" />
    </header>
</template>
