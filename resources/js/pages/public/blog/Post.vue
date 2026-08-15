<script lang="ts" setup>
import BlogPostNav from '@/components/blog/BlogPostNav.vue';
import BorderDivider from '@/components/blog/BorderDivider.vue';
import PostBackLink from '@/components/blog/PostBackLink.vue';
import PostContent from '@/components/blog/PostContent.vue';
import PostExtensions from '@/components/blog/PostExtensions.vue';
import PostExternalLinks from '@/components/blog/PostExternalLinks.vue';
import PostHeader from '@/components/blog/PostHeader.vue';
import PostRelatedPosts from '@/components/blog/PostRelatedPosts.vue';
import PublicBlogShell from '@/components/blog/PublicBlogShell.vue';
import type { SEO } from '@/types';
import type { Blog, BlogChrome, PostDetails, PostListing, ViewStats } from '@/types/blog.types';
import { computed } from 'vue';

const props = defineProps<{
    blog: Blog;
    post: PostDetails;
    chrome: BlogChrome;
    listing: PostListing;
    seo?: SEO;
    viewStats?: ViewStats | null;
}>();

const isListed = computed(() => props.post.visibility !== 'unlisted');
const visibleListing = computed(() => (isListed.value ? props.listing : null));
const postsListSpacingClass = computed(() => (props.chrome.sidebar ? '' : 'mt-6'));
const landingUrl = computed(() => props.chrome.navigation?.landingUrl ?? props.blog.url);
</script>

<template>
    <PublicBlogShell
        :blog="blog"
        :chrome="chrome"
        :contentSpacingClass="postsListSpacingClass"
        :listing="visibleListing"
        middleDividerClass="mt-12 mb-4"
    >
        <template #header>
            <PostHeader :locale="chrome.locale" :post="post" :seo="seo" :viewStats="viewStats" />
            <BorderDivider v-if="!chrome.sidebar" class="mb-8" />
        </template>

        <template #content>
            <PostContent :author="post.author" :content="post.contentHtml" />
            <PostExtensions :extensions="post.extensions || []" />
            <!-- Optional post summary -->
            <PostContent v-if="post.summaryHtml" :author="post.author" :content="post.summaryHtml" />
            <!-- Optional related posts and external links -->
            <PostRelatedPosts :items="post.relatedPosts || []" />
            <PostExternalLinks :items="post.externalLinks || []" />
        </template>

        <template #navigation>
            <BlogPostNav v-if="isListed" :activeTag="listing.activeTag" :navigation="chrome.navigation" />
            <PostBackLink v-else :landingUrl="landingUrl" />
        </template>
    </PublicBlogShell>
</template>
