<script lang="ts" setup>
import BlogPostNav from '@/components/blog/BlogPostNav.vue';
import BlogPostsList from '@/components/blog/BlogPostsList.vue';
import PublicNavbar from '@/components/PublicNavbar.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Blog {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
    motto?: string | null;
}

interface PostDetails {
    id: number;
    title: string;
    slug: string;
    contentHtml: string;
    published_at?: string | null;
    excerpt?: string | null;
}

interface PostItem {
    id: number;
    title: string;
    slug: string;
    excerpt?: string | null;
    published_at?: string | null;
}

interface SEO {
    title: string;
    description: string;
    canonicalUrl: string;
    ogImage: string;
    ogType: string;
    locale: string;
    publishedTime?: string | null;
    modifiedTime?: string | null;
    structuredData: Record<string, any>;
}

const props = defineProps<{
    blog: Blog;
    post: PostDetails;
    posts: PostItem[];
    sidebarPosition: 'left' | 'right' | 'none';
    pagination?: { links: { url: string | null; label: string; active: boolean }[] } | null;
    navigation?: {
        prevPost?: { title: string; slug: string; url: string } | null;
        nextPost?: { title: string; slug: string; url: string } | null;
        landingUrl: string;
        isLandingPage?: boolean;
    };
    locale?: string;
    seo?: SEO;
}>();

// SEO helpers - SSR compatible
const baseUrl = import.meta.env.VITE_APP_URL || 'https://osobliwy.blog';
const canonicalUrl = computed(() => props.seo?.canonicalUrl || `${baseUrl}/blogs/${props.blog.slug}/${props.post.slug}`);
const seoTitle = computed(() => props.seo?.title || `${props.post.title} - ${props.blog.name}`);
const seoDescription = computed(() => props.seo?.description || props.post.excerpt || props.post.title);
const seoImage = computed(() => props.seo?.ogImage || `${baseUrl}/og-image.png`);

// Structured data for SEO
const structuredData = computed(() =>
    props.seo?.structuredData || {
        '@context': 'https://schema.org',
        '@type': 'BlogPosting',
        headline: props.post.title,
        description: seoDescription.value,
        url: canonicalUrl.value,
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
            '@id': canonicalUrl.value,
        },
    },
);
</script>

<template>
    <Head :title="seoTitle">
        <!-- Primary Meta Tags -->
        <meta :content="seoDescription" name="description" />
        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />
        <link :href="canonicalUrl" rel="canonical" />
        <meta v-if="locale" :content="locale" http-equiv="content-language" />

        <!-- Open Graph / Facebook -->
        <meta :content="seo?.ogType || 'article'" property="og:type" />
        <meta :content="seoTitle" property="og:title" />
        <meta :content="seoDescription" property="og:description" />
        <meta :content="canonicalUrl" property="og:url" />
        <meta :content="seoImage" property="og:image" />
        <meta content="1200" property="og:image:width" />
        <meta content="630" property="og:image:height" />
        <meta :content="locale || 'en'" property="og:locale" />
        <meta v-if="seo?.publishedTime" :content="seo.publishedTime" property="article:published_time" />
        <meta v-if="seo?.modifiedTime" :content="seo.modifiedTime" property="article:modified_time" />

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

            <header class="mb-4">
                <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-400">{{ post.title }}</h1>
                <p v-if="post.published_at" class="text-sm text-gray-800 italic dark:text-gray-300">Published {{ post.published_at }}</p>
            </header>

            <!-- Add separation line under header when no sidebar -->
            <div v-if="sidebarPosition === 'none'" class="mb-6 border-b border-gray-200 dark:border-gray-700"></div>

            <div v-if="sidebarPosition === 'left'" class="flex items-start gap-8">
                <aside class="w-[280px]">
                    <BlogPostsList :blogSlug="blog.slug" :pagination="pagination" :posts="posts" />
                </aside>
                <main class="min-w-0 flex-1">
                    <article class="prose max-w-none" v-html="post.contentHtml" />
                </main>
            </div>

            <div v-else-if="sidebarPosition === 'right'" class="flex items-start gap-8">
                <main class="min-w-0 flex-1">
                    <article class="prose max-w-none" v-html="post.contentHtml" />
                </main>
                <aside class="w-[280px]">
                    <BlogPostsList :blogSlug="blog.slug" :pagination="pagination" :posts="posts" />
                </aside>
            </div>

            <div v-else>
                <main class="min-w-0 flex-1">
                    <article class="prose max-w-none" v-html="post.contentHtml" />
                </main>
                <BlogPostsList :blogSlug="blog.slug" :pagination="pagination" :posts="posts" class="mt-6" />
            </div>

            <!-- Post Navigation at bottom -->
            <BlogPostNav :navigation="navigation" />
        </div>
    </div>
</template>
