<script lang="ts" setup>
import BlogPostNav from '@/components/blog/BlogPostNav.vue';
import BlogPostsList from '@/components/blog/BlogPostsList.vue';
import PostContent from '@/components/blog/PostContent.vue';
import PublicNavbar from '@/components/PublicNavbar.vue';
import SeoHead from '@/components/seo/SeoHead.vue';
import BorderDivider from '@/components/blog/BorderDivider.vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

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
    author: string;
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

const { t } = useI18n();
const author = computed(() => t('landing.post.author', ''));
const published = computed(() => t('landing.post.published', 'Published:'));

// SEO helpers - SSR compatible
const baseUrl = import.meta.env.VITE_APP_URL || 'https://osobliwy.blog';
const canonicalUrl = computed(() => props.seo?.canonicalUrl || `${baseUrl}/blogs/${props.blog.slug}/${props.post.slug}`);
const seoTitle = computed(() => props.seo?.title || `${props.post.title} - ${props.blog.name}`);
const seoDescription = computed(() => props.seo?.description || props.post.excerpt || props.post.title);
const seoImage = computed(() => props.seo?.ogImage || `${baseUrl}/og-image.png`);
const ogType = computed(() => props.seo?.ogType || 'article');
const publishedTime = computed(() => props.seo?.publishedTime || null);
const modifiedTime = computed(() => props.seo?.modifiedTime || null);

// Structured data for SEO
const structuredData = computed(
    () =>
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
    <SeoHead
        :canonical-url="canonicalUrl"
        :description="seoDescription"
        :locale="locale"
        :modified-time="modifiedTime"
        :og-image="seoImage"
        :og-type="ogType"
        :published-time="publishedTime"
        :structured-data="structuredData"
        :title="seoTitle"
    />

    <div class="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a]">
        <PublicNavbar />

        <div class="mx-auto w-full max-w-[1024px] p-4">
            <BorderDivider class="mb-4" />

            <header class="mb-4">
                <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-400">{{ post.title }}</h1>
                <p v-if="post.published_at" class="my-2 text-sm text-gray-800 italic dark:text-gray-300">{{ published }} {{ post.published_at }}</p>
                <p v-if="post.author" class="text-md text-gray-900 dark:text-gray-200">{{ author }} {{ props.post.author }}</p>
            </header>

            <!-- Add separation line under header when no sidebar -->
            <BorderDivider v-if="sidebarPosition === 'none'" class="mb-8" />

            <div v-if="sidebarPosition === 'left'" class="flex items-start gap-8">
                <aside class="w-[280px]">
                    <BlogPostsList :blogSlug="blog.slug" :pagination="pagination" :posts="posts" />
                </aside>
                <PostContent :author="post.author" :content="post.contentHtml" />
            </div>

            <div v-else-if="sidebarPosition === 'right'" class="flex items-start gap-8">
                <PostContent :author="post.author" :content="post.contentHtml" />
                <aside class="w-[280px]">
                    <BlogPostsList :blogSlug="blog.slug" :pagination="pagination" :posts="posts" />
                </aside>
            </div>

            <div v-else>
                <PostContent :author="post.author" :content="post.contentHtml" />
                <BorderDivider class="mb-4" />
                <BlogPostsList :blogSlug="blog.slug" :pagination="pagination" :posts="posts" class="mt-6" />
            </div>

            <!-- Post Navigation at bottom -->
            <BlogPostNav :navigation="navigation" />
        </div>
    </div>
</template>
