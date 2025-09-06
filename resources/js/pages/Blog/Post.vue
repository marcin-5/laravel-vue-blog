<script lang="ts" setup>
import BlogPostNav from '@/components/BlogPostNav.vue';
import PublicNavbar from '@/components/PublicNavbar.vue';
import { ensureNamespace } from '@/i18n';
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import BlogPostsList from '../../components/BlogPostsList.vue';

interface Blog {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
}

interface PostDetails {
    id: number;
    title: string;
    slug: string;
    contentHtml: string;
    published_at?: string | null;
}

interface PostItem {
    id: number;
    title: string;
    slug: string;
    excerpt?: string | null;
    published_at?: string | null;
}

defineProps<{
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
}>();

const { locale } = useI18n();
// Ensure 'landing' namespace is available for nav labels (SSR-safe with Suspense)
await ensureNamespace(locale.value, 'landing');
</script>

<template>
    <Head :title="`${post.title} - ${blog.name}`">
        <meta :content="post.title" name="description" />
    </Head>

    <div class="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a]">
        <PublicNavbar />

        <div class="mx-auto w-full max-w-[1024px] p-4">
            <!-- Add separation line under navbar when no sidebar -->
            <div v-if="sidebarPosition === 'none'" class="mb-4 border-b border-gray-200 dark:border-gray-700"></div>

            <header class="mb-4">
                <h1 class="text-2xl font-bold">{{ post.title }}</h1>
                <p v-if="post.published_at" class="text-gray-600">Published {{ post.published_at }}</p>
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
