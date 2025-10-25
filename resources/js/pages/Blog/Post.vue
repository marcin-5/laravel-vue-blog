<script lang="ts" setup>
import BlogPostNav from '@/components/blog/BlogPostNav.vue';
import BlogPostsList from '@/components/blog/BlogPostsList.vue';
import BorderDivider from '@/components/blog/BorderDivider.vue';
import PostContent from '@/components/blog/PostContent.vue';
import PublicNavbar from '@/components/PublicNavbar.vue';
import SeoHead from '@/components/seo/SeoHead.vue';
import type { Blog, Navigation, Pagination, PostDetails, PostItem, SEO } from '@/types/blog';
import { DEFAULT_APP_URL } from '@/types/blog';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    blog: Blog;
    post: PostDetails;
    posts: PostItem[];
    sidebarPosition: 'left' | 'right' | 'none';
    pagination?: Pagination | null;
    navigation?: Navigation;
    locale?: string;
    seo?: SEO;
}>();

const { t } = useI18n();

// Internationalization
const authorLabel = computed(() => t('blog.post.author', ''));
const publishedLabel = computed(() => t('blog.post.published', 'Published:'));

// Configuration
const applicationBaseUrl = import.meta.env.VITE_APP_URL || DEFAULT_APP_URL;

// SEO URL builders
function createPostUrl(blogSlug: string, postSlug: string): string {
    return `${applicationBaseUrl}/blogs/${blogSlug}/${postSlug}`;
}

function createDefaultOgImage(): string {
    return `${applicationBaseUrl}/og-image.png`;
}

// SEO computed properties
const postCanonicalUrl = computed(() => props.seo?.canonicalUrl || createPostUrl(props.blog.slug, props.post.slug));

const postSeoTitle = computed(() => props.seo?.title || `${props.post.title} - ${props.blog.name}`);

const postSeoDescription = computed(() => props.seo?.description || props.post.excerpt || props.post.title);

const postSeoImage = computed(() => props.seo?.ogImage || createDefaultOgImage());

const postOgType = computed(() => props.seo?.ogType || 'article');
const postPublishedTime = computed(() => props.seo?.publishedTime || null);
const postModifiedTime = computed(() => props.seo?.modifiedTime || null);

// Layout checks
const hasSidebar = computed(() => props.sidebarPosition !== 'none');
const isLeftSidebar = computed(() => props.sidebarPosition === 'left');
const isRightSidebar = computed(() => props.sidebarPosition === 'right');

// Structured data for SEO
const postStructuredData = computed(
    () =>
        props.seo?.structuredData || {
            '@context': 'https://schema.org',
            '@type': 'BlogPosting',
            headline: props.post.title,
            description: postSeoDescription.value,
            url: postCanonicalUrl.value,
            datePublished: props.seo?.publishedTime,
            dateModified: props.seo?.modifiedTime,
            author: {
                '@type': 'Organization',
                name: props.blog.name,
            },
            publisher: {
                '@type': 'Organization',
                name: props.blog.name,
            },
            mainEntityOfPage: {
                '@type': 'WebPage',
                '@id': postCanonicalUrl.value,
            },
        },
);
</script>

<template>
    <SeoHead
        :canonical-url="postCanonicalUrl"
        :description="postSeoDescription"
        :locale="locale"
        :modified-time="postModifiedTime"
        :og-image="postSeoImage"
        :og-type="postOgType"
        :published-time="postPublishedTime"
        :structured-data="postStructuredData"
        :title="postSeoTitle"
    />

    <div class="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a]">
        <PublicNavbar />

        <div class="mx-auto w-full max-w-[1024px] p-4">
            <BorderDivider class="mb-4" />

            <header class="mb-4">
                <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-400">{{ post.title }}</h1>
                <p v-if="post.published_at" class="my-2 text-sm text-gray-800 italic dark:text-gray-300">
                    {{ publishedLabel }} {{ post.published_at }}
                </p>
                <p v-if="post.author" class="text-md text-gray-900 dark:text-gray-200">
                    {{ authorLabel }}
                    <a :href="`mailto:${post.author_email}`">{{ post.author }}</a>
                </p>
            </header>

            <!-- Add separation line under header when no sidebar -->
            <BorderDivider v-if="!hasSidebar" class="mb-8" />

            <!-- Left sidebar layout -->
            <div v-if="isLeftSidebar" class="flex items-start gap-8">
                <aside class="w-[280px]">
                    <BlogPostsList :blogSlug="blog.slug" :pagination="pagination" :posts="posts" />
                </aside>
                <PostContent :author="post.author" :content="post.contentHtml" />
            </div>

            <!-- Right sidebar layout -->
            <div v-else-if="isRightSidebar" class="flex items-start gap-8">
                <PostContent :author="post.author" :content="post.contentHtml" />
                <aside class="w-[280px]">
                    <BlogPostsList :blogSlug="blog.slug" :pagination="pagination" :posts="posts" />
                </aside>
            </div>

            <!-- No sidebar layout -->
            <div v-else>
                <PostContent :author="post.author" :content="post.contentHtml" />
                <BorderDivider class="mt-12 mb-4" />
                <BlogPostsList :blogSlug="blog.slug" :pagination="pagination" :posts="posts" class="mt-6" />
            </div>

            <!-- Post Navigation at bottom -->
            <BlogPostNav :navigation="navigation" />
        </div>
    </div>
</template>
