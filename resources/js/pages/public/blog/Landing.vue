<script lang="ts" setup>
import BlogHeader from '@/components/blog/BlogHeader.vue';
import PublicBlogShell from '@/components/blog/PublicBlogShell.vue';
import ScrollToPostsLink from '@/components/blog/ScrollToPostsLink.vue';
import type { Blog, BlogChrome, PostListing, ViewStats } from '@/types/blog.types';
import { handleContentClick } from '@/utils/domUtils';
import { hasContent } from '@/utils/stringUtils';
import { computed } from 'vue';
import { SEO } from '@/types';

const props = defineProps<{
    blog: Blog;
    landingHtml: string;
    chrome: BlogChrome;
    listing: PostListing;
    seo?: SEO;
    viewStats?: ViewStats | null;
}>();

const hasLandingContent = computed(() => hasContent(props.landingHtml));

const postsListSpacingClass = computed(() => (hasLandingContent.value ? 'mt-6' : ''));
</script>

<template>
    <PublicBlogShell :blog="blog" :chrome="chrome" :contentSpacingClass="postsListSpacingClass" :listing="listing">
        <template #header>
            <BlogHeader :blog="blog" :viewStats="viewStats" />
            <ScrollToPostsLink :class="{ 'xl:hidden': chrome.sidebar }" />
        </template>

        <template #content>
            <div v-if="hasLandingContent" class="prose max-w-none text-primary" @click="handleContentClick" v-html="landingHtml" />
        </template>
    </PublicBlogShell>
</template>
