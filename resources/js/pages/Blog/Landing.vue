<script lang="ts" setup>
import BlogHeader from '@/components/blog/BlogHeader.vue';
import BlogPostNav from '@/components/blog/BlogPostNav.vue';
import BlogPostsList from '@/components/blog/BlogPostsList.vue';
import PublicNavbar from '@/components/PublicNavbar.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Blog {
    id: number;
    name: string;
    slug: string;
    descriptionHtml?: string | null;
    motto?: string | null;
}

interface PostItem {
    id: number;
    title: string;
    slug: string;
    excerpt?: string | null;
    published_at?: string | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Pagination {
    links: PaginationLink[];
}

interface NavPost {
    title: string;
    slug: string;
    url: string;
}

interface Navigation {
    prevPost?: NavPost | null;
    nextPost?: NavPost | null;
    landingUrl: string;
    isLandingPage?: boolean;
}

const props = defineProps<{
    blog: Blog;
    landingHtml: string;
    posts: PostItem[];
    pagination?: Pagination | null;
    // numeric sidebar value (-50..50).
    sidebar?: number;
    metaDescription: string;
    navigation?: Navigation;
    locale?: string;
}>();
const hasLanding = computed(() => !!props.landingHtml);

function getRandomMotto(motto: string | null | undefined): string | null {
    if (!motto) return null;
    const mottos = motto.split('\n\n').filter((m) => m.trim());
    if (mottos.length === 0) return null;
    return mottos[Math.floor(Math.random() * mottos.length)].trim();
}

const displayedMotto = getRandomMotto(props.blog.motto);
// Compute sidebar layout values
const sidebarValue = computed(() => props.sidebar ?? 0);
const sidebarWidth = computed(() => Math.min(50, Math.max(0, Math.abs(sidebarValue.value))));
const hasSidebar = computed(() => sidebarWidth.value > 0);
const isSidebarRight = computed(() => sidebarValue.value > 0);

// SEO helpers - SSR compatible
const baseUrl = import.meta.env.VITE_APP_URL || 'https://osobliwy.blog';
const canonicalUrl = computed(() => `${baseUrl}/blogs/${props.blog.slug}`);
const seoTitle = computed(() => props.blog.name);
const seoDescription = computed(() => props.metaDescription || props.blog.name);
const seoImage = computed(() => `${baseUrl}/og-image.png`);

// Structured data for SEO
const structuredData = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'Blog',
    name: seoTitle.value,
    url: canonicalUrl.value,
    description: seoDescription.value,
    author: {
        '@type': 'Organization',
        name: seoTitle.value,
    },
    blogPost: props.posts.map((post) => ({
        '@type': 'BlogPosting',
        headline: post.title,
        url: `${baseUrl}/blogs/${props.blog.slug}/${post.slug}`,
        datePublished: post.published_at,
        description: post.excerpt?.replace(/<[^>]*>/g, '').substring(0, 200),
    })),
}));
</script>

<template>
    <Head :title="seoTitle">
        <!-- Primary Meta Tags -->
        <meta :content="seoDescription" name="description" />
        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />
        <link :href="canonicalUrl" rel="canonical" />
        <meta v-if="locale" :content="locale" http-equiv="content-language" />

        <!-- Open Graph / Facebook -->
        <meta content="blog" property="og:type" />
        <meta :content="seoTitle" property="og:title" />
        <meta :content="seoDescription" property="og:description" />
        <meta :content="canonicalUrl" property="og:url" />
        <meta :content="seoImage" property="og:image" />
        <meta content="1200" property="og:image:width" />
        <meta content="630" property="og:image:height" />
        <meta :content="locale || 'en'" property="og:locale" />

        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta :content="seoTitle" name="twitter:title" />
        <meta :content="seoDescription" name="twitter:description" />
        <meta :content="seoImage" name="twitter:image" />

        <!-- Structured Data -->
        <component :is="'script'" type="application/ld+json" v-html="JSON.stringify(structuredData)" />
    </Head>
    <div class="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a]">
        <PublicNavbar />
        <div class="mx-auto w-full max-w-[1024px] p-4">
            <div class="mb-4 border-b border-gray-200 dark:border-gray-700"></div>

            <!-- Layout without sidebar -->
            <template v-if="!hasSidebar">
                <BlogHeader :blog="blog" :displayedMotto="displayedMotto" />
                <div class="mb-6 border-b border-gray-200 dark:border-gray-700"></div>
                <main v-if="hasLanding" class="min-w-0 flex-1">
                    <div class="prose max-w-none" v-html="landingHtml" />
                </main>
                <BlogPostsList :blogSlug="blog.slug" :class="{ 'mt-6': hasLanding }" :pagination="pagination" :posts="posts" />
            </template>

            <!-- Layout with sidebar -->
            <div v-else class="flex items-start gap-8">
                <aside :class="{ 'order-2': isSidebarRight }" :style="{ width: sidebarWidth + '%', flex: '0 0 ' + sidebarWidth + '%' }">
                    <BlogPostsList :blogSlug="blog.slug" :pagination="pagination" :posts="posts" />
                </aside>
                <main
                    :class="{ 'order-1': isSidebarRight }"
                    :style="{ width: 100 - sidebarWidth + '%', flex: '1 1 ' + (100 - sidebarWidth) + '%' }"
                    class="min-w-0 flex-1"
                >
                    <BlogHeader :blog="blog" :displayedMotto="displayedMotto" />
                    <div v-if="hasLanding" class="prose max-w-none" v-html="landingHtml" />
                </main>
            </div>

            <!-- Navigation at bottom -->
            <BlogPostNav :navigation="navigation" />
        </div>
    </div>
</template>
