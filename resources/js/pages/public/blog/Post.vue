<script lang="ts" setup>
import BlogLayout from '@/components/blog/BlogLayout.vue';
import BlogPostNav from '@/components/blog/BlogPostNav.vue';
import BlogPostsList from '@/components/blog/BlogPostsList.vue';
import BorderDivider from '@/components/blog/BorderDivider.vue';
import PostContent from '@/components/blog/PostContent.vue';
import PostExtensions from '@/components/blog/PostExtensions.vue';
import PostExternalLinks from '@/components/blog/PostExternalLinks.vue';
import PostHeader from '@/components/blog/PostHeader.vue';
import PostRelatedPosts from '@/components/blog/PostRelatedPosts.vue';
import type { SEO } from '@/types';
import type { Blog, Navigation, Pagination, PostDetails, PostItem, ViewStats } from '@/types/blog.types';
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    blog: Blog;
    post: PostDetails;
    posts: PostItem[];
    sidebar?: number;
    pagination?: Pagination | null;
    navigation?: Navigation;
    locale?: string;
    seo?: SEO;
    viewStats?: ViewStats | null;
}>();

const { t } = useI18n();

// SEO timestamps
const postPublishedTime = computed(() => props.seo?.publishedTime || null);
const postModifiedTime = computed(() => props.seo?.modifiedTime || null);

// Derived post state
const isListed = computed(() => props.post.visibility !== 'unlisted');
const postExtensionsList = computed(() => props.post.extensions || []);
const relatedPosts = computed(() => props.post.relatedPosts || []);
const externalLinks = computed(() => props.post.externalLinks || []);

// Shared prop objects to avoid template repetition
const postHeaderProps = computed(() => ({
    locale: props.locale,
    modifiedTime: postModifiedTime.value,
    post: props.post,
    publishedTime: postPublishedTime.value,
    viewStats: props.viewStats,
}));

const blogPostsListProps = computed(() => ({
    blogId: props.blog.id,
    blogSlug: props.blog.slug,
    pagination: props.pagination,
    posts: props.posts,
}));

// Navigation
const navigateBack = () => {
    router.visit(props.navigation?.landingUrl ?? `/${props.blog.slug}`);
};
</script>

<template>
    <Head v-if="seo?.title" :title="seo.title" />

    <BlogLayout :isPublic="true" :sidebar="sidebar" :theme="blog.theme">
        <template #top-divider>
            <BorderDivider class="mb-4" />
        </template>

        <template #header>
            <PostHeader v-bind="postHeaderProps" />
            <BorderDivider v-if="!sidebar" class="mb-8" />
        </template>

        <template #content>
            <PostContent :author="post.author" :content="post.contentHtml" />
            <PostExtensions :extensions="postExtensionsList" />
            <!-- Optional post summary -->
            <PostContent v-if="post.summaryHtml" :author="post.author" :content="post.summaryHtml" />
            <!-- Optional related posts and external links -->
            <PostRelatedPosts :items="relatedPosts" />
            <PostExternalLinks :items="externalLinks" />
        </template>

        <template #middle-divider>
            <BorderDivider class="mt-12 mb-4" />
        </template>

        <template #sidebar-content>
            <BlogPostsList v-if="isListed" :class="{ 'mt-6': !sidebar }" v-bind="blogPostsListProps" />
        </template>

        <template #navigation>
            <BlogPostNav v-if="isListed" :navigation="navigation" />
            <div v-else class="flex items-center">
                <button
                    class="inline-flex items-center rounded-sm border border-border bg-card px-3 py-2 text-sm text-primary transition-colors hover:bg-secondary"
                    @click="navigateBack"
                >
                    <ArrowLeft class="mr-2 size-4" />
                    <span>{{ t('blog.post_nav.back') }}</span>
                </button>
            </div>
        </template>
    </BlogLayout>
</template>
