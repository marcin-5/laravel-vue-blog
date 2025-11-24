<script lang="ts" setup>
import ViewStats from '@/components/blog/ViewStats.vue';
import type { Blog } from '@/types/blog.types';
import '@fontsource/noto-serif';

defineProps<{
    blog: Blog & {
        authorName?: string;
        authorEmail?: string;
    };
    displayedMotto: string | null;
    viewStats: {
        total: number;
    };
}>();
</script>

<template>
    <header class="mb-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-400">
                {{ blog.name }}
            </h1>
            <ViewStats :total="viewStats.total"></ViewStats>
        </div>
        <p v-if="displayedMotto" class="mt-2 mb-12 font-serif text-gray-800 italic dark:text-gray-200">
            {{ displayedMotto }}
        </p>
    </header>
    <section>
        <div v-if="blog.descriptionHtml" class="prose prose-slate dark:prose-invert max-w-none" v-html="blog.descriptionHtml" />
    </section>
    <footer>
        <div v-if="blog.authorName && blog.authorEmail" class="author text-md mr-12 mb-12 text-end font-serif text-slate-700 dark:text-slate-300">
            <a :href="`mailto:${blog.authorEmail}`">{{ blog.authorName }}</a>
        </div>
    </footer>
</template>
