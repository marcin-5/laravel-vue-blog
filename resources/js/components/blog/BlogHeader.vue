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
        unique?: number;
    };
}>();
</script>

<template>
    <header class="mb-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-foreground">
                {{ blog.name }}
            </h1>
            <ViewStats :total="viewStats.total" :unique="viewStats.unique"></ViewStats>
        </div>
        <p v-if="displayedMotto" class="mt-2 mb-12 font-serif text-foreground italic opacity-80">
            {{ displayedMotto }}
        </p>
    </header>
    <section>
        <div v-if="blog.descriptionHtml" class="prose max-w-none text-primary" v-html="blog.descriptionHtml" />
    </section>
    <footer>
        <div v-if="blog.authorName && blog.authorEmail" class="author text-md mr-12 mb-12 text-end font-serif text-muted-foreground">
            <a :href="`mailto:${blog.authorEmail}`" class="hover:text-foreground">{{ blog.authorName }}</a>
        </div>
    </footer>
</template>
