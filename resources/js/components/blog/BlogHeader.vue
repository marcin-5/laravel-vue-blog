<script lang="ts" setup>
import ViewStatsComponent from '@/components/blog/ViewStats.vue';
import type { Blog, ViewStats } from '@/types/blog.types';
import { blogBodyTextStyle, blogHeaderStyle } from '@/utils/blogStyles';

defineProps<{
    blog: Blog & {
        authorName?: string;
        authorEmail?: string;
    };
    viewStats?: ViewStats | null;
}>();
</script>

<template>
    <header :style="blogHeaderStyle" class="mb-4">
        <h1 class="mb-2 font-[inherit] text-[1em] leading-tight font-bold text-foreground">{{ blog.name }}</h1>
        <ViewStatsComponent
            v-if="viewStats"
            :anonymous="viewStats?.anonymous"
            :bots="viewStats?.bots"
            :consented="viewStats?.consented"
            :markdown="viewStats?.markdown"
        />
        <p
            v-if="blog.displayedMotto"
            :style="{ fontStyle: 'var(--blog-motto-style)', fontFamily: 'var(--blog-motto-font)', fontSize: 'calc(1rem * var(--blog-motto-scale))' }"
            class="mt-8 mb-8 max-w-11/12 text-right text-foreground opacity-80"
        >
            {{ blog.displayedMotto }}
        </p>
    </header>
    <section>
        <div v-if="blog.descriptionHtml" :style="blogBodyTextStyle" class="prose max-w-none text-primary/90" v-html="blog.descriptionHtml" />
    </section>
    <footer :style="{ fontFamily: 'var(--blog-footer-font)', fontSize: 'calc(1rem * var(--blog-body-scale))' }" class="origin-right">
        <div v-if="blog.authorName && blog.authorEmail" class="author mb-8 max-w-11/12 text-end text-muted-foreground">
            <a :href="`mailto:${blog.authorEmail}`" class="hover:text-primary">{{ blog.authorName }}</a>
        </div>
    </footer>
</template>
