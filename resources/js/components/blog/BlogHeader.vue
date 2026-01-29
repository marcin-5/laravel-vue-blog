<script lang="ts" setup>
import ViewStats from '@/components/blog/ViewStats.vue';
import type { Blog } from '@/types/blog.types';

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
    <header :style="{ fontFamily: 'var(--blog-header-font)', fontSize: 'calc(2rem * var(--blog-header-scale))' }" class="mb-4">
        <div class="flex items-center justify-between">
            <h1 class="font-[inherit] text-[1em] leading-tight font-bold text-foreground">
                {{ blog.name }}
            </h1>
            <ViewStats :total="viewStats.total" :unique="viewStats.unique"></ViewStats>
        </div>
        <p
            v-if="displayedMotto"
            :style="{ fontStyle: 'var(--blog-motto-style)', fontFamily: 'var(--blog-motto-font)', fontSize: 'calc(1rem * var(--blog-motto-scale))' }"
            class="mt-2 mb-12 text-foreground opacity-80"
        >
            {{ displayedMotto }}
        </p>
    </header>
    <section>
        <div
            v-if="blog.descriptionHtml"
            :style="{ fontFamily: 'var(--blog-body-font)', fontSize: 'calc(1rem * var(--blog-body-scale))' }"
            class="prose max-w-none text-primary/90"
            v-html="blog.descriptionHtml"
        />
    </section>
    <footer :style="{ fontFamily: 'var(--blog-footer-font)', fontSize: 'calc(1rem * var(--blog-body-scale))' }" class="origin-right">
        <div v-if="blog.authorName && blog.authorEmail" class="author mr-12 mb-12 text-end text-muted-foreground">
            <a :href="`mailto:${blog.authorEmail}`" class="hover:text-primary">{{ blog.authorName }}</a>
        </div>
    </footer>
</template>
