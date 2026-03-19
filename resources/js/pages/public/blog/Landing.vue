<script lang="ts" setup>
import BlogFooter from '@/components/blog/BlogFooter.vue';
import BlogHeader from '@/components/blog/BlogHeader.vue';
import BlogLayout from '@/components/blog/BlogLayout.vue';
import BlogPostNav from '@/components/blog/BlogPostNav.vue';
import BlogPostsList from '@/components/blog/BlogPostsList.vue';
import BorderDivider from '@/components/blog/BorderDivider.vue';
import ScrollToPostsLink from '@/components/blog/ScrollToPostsLink.vue';
import type { Blog, Navigation, Pagination, PostItem, ViewStats } from '@/types/blog.types';
import { handleContentClick } from '@/utils/domUtils';
import { hasContent, selectRandomMotto } from '@/utils/stringUtils';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    blog: Blog;
    landingHtml: string;
    footerHtml?: string;
    posts: PostItem[];
    pagination?: Pagination | null;
    /** Numeric sidebar value (-50..50). */
    sidebar?: number;
    metaDescription: string;
    navigation?: Navigation;
    locale?: string;
    viewStats: ViewStats | null;
    seo?: {
        title: string;
    };
}>();

// Content availability checks
const hasLandingContent = computed(() => hasContent(props.landingHtml));
const hasFooterContent = computed(() => hasContent(props.footerHtml));

// Motto selection
const displayedMotto = selectRandomMotto(props.blog.motto);

const postsListSpacingClass = computed(() => (hasLandingContent.value ? 'mt-6' : ''));
</script>

<template>
    <Head v-if="seo?.title" :title="seo.title" />

    <BlogLayout :isPublic="true" :sidebar="sidebar" :theme="blog.theme">
        <template #top-divider>
            <BorderDivider class="mb-4" />
        </template>

        <template #header>
            <BlogHeader :blog="blog" :displayedMotto="displayedMotto" :viewStats="viewStats" />
            <ScrollToPostsLink />
        </template>

        <template #content>
            <div v-if="hasLandingContent" class="prose max-w-none text-primary" @click="handleContentClick" v-html="landingHtml" />
        </template>

        <template #middle-divider>
            <BorderDivider v-if="!sidebar" class="my-4" />
        </template>

        <template #sidebar-content>
            <BlogPostsList
                id="posts-list"
                :blogId="blog.id"
                :blogSlug="blog.slug"
                :class="postsListSpacingClass"
                :pagination="pagination"
                :posts="posts"
            />
        </template>

        <template #navigation>
            <BlogPostNav :navigation="navigation" />
        </template>

        <template #footer>
            <template v-if="hasFooterContent">
                <BorderDivider class="my-4" />
                <BlogFooter :html="footerHtml || ''" />
            </template>
        </template>
    </BlogLayout>
</template>
