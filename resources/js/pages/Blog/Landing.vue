<script lang="ts" setup>
import BlogFooter from '@/components/blog/BlogFooter.vue';
import BlogHeader from '@/components/blog/BlogHeader.vue';
import BlogPostNav from '@/components/blog/BlogPostNav.vue';
import BlogPostsList from '@/components/blog/BlogPostsList.vue';
import BorderDivider from '@/components/blog/BorderDivider.vue';
import PublicNavbar from '@/components/PublicNavbar.vue';
import SeoHead from '@/components/seo/SeoHead.vue';
import { DEFAULT_APP_URL, EXCERPT_MAX_LENGTH, SIDEBAR_MAX_WIDTH, SIDEBAR_MIN_WIDTH } from '@/types/blog';
import type { Blog, Navigation, Pagination, PostItem } from '@/types/blog.types';
import { computed } from 'vue';

const props = defineProps<{
    blog: Blog;
    landingHtml: string;
    footerHtml?: string;
    posts: PostItem[];
    pagination?: Pagination | null;
    // numeric sidebar value (-50..50).
    sidebar?: number;
    metaDescription: string;
    navigation?: Navigation;
    locale?: string;
}>();

// Content availability checks
const hasLandingContent = computed(() => !!props.landingHtml);
const hasFooterContent = computed(() => !!(props.footerHtml && props.footerHtml.trim()));

// Motto selection
function selectRandomMottoFromList(mottoText: string | null | undefined): string | null {
    if (!mottoText) return null;

    const mottoList = mottoText.split('\n\n').filter((motto) => motto.trim());
    if (mottoList.length === 0) return null;

    const randomIndex = Math.floor(Math.random() * mottoList.length);
    return mottoList[randomIndex].trim();
}

const displayedMotto = selectRandomMottoFromList(props.blog.motto);

// Sidebar layout calculations
const sidebarPercentage = computed(() => props.sidebar ?? 0);
const normalizedSidebarWidth = computed(() => Math.min(SIDEBAR_MAX_WIDTH, Math.max(SIDEBAR_MIN_WIDTH, Math.abs(sidebarPercentage.value))));
const hasSidebarLayout = computed(() => normalizedSidebarWidth.value > SIDEBAR_MIN_WIDTH);
const isSidebarPositionedRight = computed(() => sidebarPercentage.value > 0);
const mainContentWidth = computed(() => 100 - normalizedSidebarWidth.value);

// SEO configuration
const applicationBaseUrl = import.meta.env.VITE_APP_URL || DEFAULT_APP_URL;
const blogCanonicalUrl = computed(() => `${applicationBaseUrl}/blogs/${props.blog.slug}`);
const blogSeoTitle = computed(() => props.blog.name);
const blogSeoDescription = computed(() => props.metaDescription || props.blog.name);
const blogSeoImage = computed(() => `${applicationBaseUrl}/og-image.png`);

// Utility functions
function stripHtmlTags(html: string): string {
    return html.replace(/<[^>]*>/g, '');
}

function createBlogPostUrl(postSlug: string): string {
    return `${applicationBaseUrl}/blogs/${props.blog.slug}/${postSlug}`;
}

// Structured data for SEO
const blogStructuredData = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'Blog',
    name: blogSeoTitle.value,
    url: blogCanonicalUrl.value,
    description: blogSeoDescription.value,
    author: {
        '@type': 'Organization',
        name: blogSeoTitle.value,
    },
    blogPost: props.posts.map((post) => ({
        '@type': 'BlogPosting',
        headline: post.title,
        url: createBlogPostUrl(post.slug),
        datePublished: post.published_at,
        description: post.excerpt ? stripHtmlTags(post.excerpt).substring(0, EXCERPT_MAX_LENGTH) : undefined,
    })),
}));
</script>

<template>
    <SeoHead
        :canonical-url="blogCanonicalUrl"
        :description="blogSeoDescription"
        :locale="locale"
        :og-image="blogSeoImage"
        :structured-data="blogStructuredData"
        :title="blogSeoTitle"
        og-type="blog"
    />
    <div class="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a]">
        <PublicNavbar />
        <div class="mx-auto w-full max-w-[1024px] p-4">
            <BorderDivider class="mb-4" />

            <!-- Layout without sidebar -->
            <template v-if="!hasSidebarLayout">
                <BlogHeader :blog="blog" :displayedMotto="displayedMotto" />
                <BorderDivider class="mb-8" />
                <main v-if="hasLandingContent" class="min-w-0 flex-1">
                    <div class="prose max-w-none" v-html="landingHtml" />
                </main>
                <BlogPostsList :blogSlug="blog.slug" :class="{ 'mt-6': hasLandingContent }" :pagination="pagination" :posts="posts" />
            </template>

            <!-- Layout with sidebar -->
            <div v-else class="flex items-start gap-8">
                <aside
                    :class="{ 'order-2': isSidebarPositionedRight }"
                    :style="{
                        width: normalizedSidebarWidth + '%',
                        flex: '0 0 ' + normalizedSidebarWidth + '%',
                    }"
                >
                    <BlogPostsList :blogSlug="blog.slug" :pagination="pagination" :posts="posts" />
                </aside>
                <main
                    :class="{ 'order-1': isSidebarPositionedRight }"
                    :style="{
                        width: mainContentWidth + '%',
                        flex: '1 1 ' + mainContentWidth + '%',
                    }"
                    class="min-w-0 flex-1"
                >
                    <BlogHeader :blog="blog" :displayedMotto="displayedMotto" />
                    <div v-if="hasLandingContent" class="prose max-w-none" v-html="landingHtml" />
                </main>
            </div>

            <!-- Navigation at bottom -->
            <BlogPostNav :navigation="navigation" />

            <!-- Footer (optional) -->
            <template v-if="hasFooterContent">
                <BorderDivider class="my-4" />
                <BlogFooter :html="footerHtml || ''" />
            </template>
        </div>
    </div>
</template>
