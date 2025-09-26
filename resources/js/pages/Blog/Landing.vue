<script lang="ts" setup>
import BlogPostNav from '@/components/BlogPostNav.vue';
import PublicNavbar from '@/components/PublicNavbar.vue';
import { Head } from '@inertiajs/vue3';
import BlogPostsList from '../../components/BlogPostsList.vue';
import { useI18nNs } from '@/composables/useI18nNs';

interface Blog {
    id: number;
    name: string;
    slug: string;
    descriptionHtml?: string | null;
}

interface PostItem {
    id: number;
    title: string;
    slug: string;
    excerpt?: string | null;
    published_at?: string | null;
}

const props = defineProps<{
    blog: Blog;
    landingHtml: string;
    posts: PostItem[];
    pagination?: { links: { url: string | null; label: string; active: boolean }[] } | null;
    // numeric sidebar value (-50..50).
    sidebar?: number;
    metaDescription: string;
    navigation?: {
        prevPost?: { title: string; slug: string; url: string } | null;
        nextPost?: { title: string; slug: string; url: string } | null;
        landingUrl: string;
        isLandingPage?: boolean;
    };
}>();

const hasLanding = !!props.landingHtml;

// Ensure 'landing' namespace is available for nav labels (SSR-safe with Suspense)
await useI18nNs('landing');

// metaDescription provided by server (PublicBlogController)
const metaDescription = props.metaDescription;

// Compute sidebar layout values
const sidebarValue = props.sidebar ?? 0;
const sidebarWidth = Math.min(50, Math.max(0, Math.abs(sidebarValue)));
</script>

<template>
    <Head :title="blog.name">
        <meta :content="metaDescription" name="description" />
    </Head>

    <div class="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a]">
        <PublicNavbar />

        <div class="mx-auto w-full max-w-[1024px] p-4">
            <div class="mb-4 border-b border-gray-200 dark:border-gray-700"></div>

            <header v-if="sidebarWidth === 0" class="mb-4">
                <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-400">{{ blog.name }}</h1>
                <div v-if="blog.descriptionHtml" class="prose prose-slate dark:prose-invert max-w-none" v-html="blog.descriptionHtml" />
            </header>

            <!-- Add separation line under blog description when no sidebar -->
            <div v-if="sidebarWidth === 0" class="mb-6 border-b border-gray-200 dark:border-gray-700"></div>

            <template v-if="hasLanding">
                <div v-if="sidebarWidth > 0 && (props.sidebar ?? 0) < 0" class="flex items-start gap-8">
                    <aside :style="{ width: sidebarWidth + '%', flex: '0 0 ' + sidebarWidth + '%' }">
                        <BlogPostsList :blogSlug="blog.slug" :pagination="pagination" :posts="posts" />
                    </aside>
                    <main :style="{ width: 100 - sidebarWidth + '%', flex: '1 1 ' + (100 - sidebarWidth) + '%' }" class="min-w-0 flex-1">
                        <header class="mb-4">
                            <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-400">{{ blog.name }}</h1>
                            <div v-if="blog.descriptionHtml" class="prose prose-slate dark:prose-invert max-w-none" v-html="blog.descriptionHtml" />
                        </header>
                        <div class="prose max-w-none" v-html="landingHtml" />
                    </main>
                </div>

                <div v-else-if="sidebarWidth > 0 && (props.sidebar ?? 0) > 0" class="flex items-start gap-8">
                    <main :style="{ width: 100 - sidebarWidth + '%', flex: '1 1 ' + (100 - sidebarWidth) + '%' }" class="min-w-0 flex-1">
                        <header class="mb-4">
                            <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-400">{{ blog.name }}</h1>
                            <div v-if="blog.descriptionHtml" class="prose prose-slate dark:prose-invert max-w-none" v-html="blog.descriptionHtml" />
                        </header>
                        <div class="prose max-w-none" v-html="landingHtml" />
                    </main>
                    <aside :style="{ width: sidebarWidth + '%', flex: '0 0 ' + sidebarWidth + '%' }">
                        <BlogPostsList :blogSlug="blog.slug" :pagination="pagination" :posts="posts" />
                    </aside>
                </div>

                <div v-else>
                    <main class="min-w-0 flex-1">
                        <div class="prose max-w-none" v-html="landingHtml" />
                    </main>
                    <BlogPostsList :blogSlug="blog.slug" :pagination="pagination" :posts="posts" class="mt-6" />
                </div>
            </template>

            <template v-else>
                <div v-if="sidebarWidth > 0 && (props.sidebar ?? 0) < 0" class="flex items-start gap-8">
                    <aside :style="{ width: sidebarWidth + '%', flex: '0 0 ' + sidebarWidth + '%' }">
                        <BlogPostsList :blogSlug="blog.slug" :pagination="pagination" :posts="posts" />
                    </aside>
                    <main :style="{ width: 100 - sidebarWidth + '%', flex: '1 1 ' + (100 - sidebarWidth) + '%' }" class="min-w-0 flex-1">
                        <header class="mb-4">
                            <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-400">{{ blog.name }}</h1>
                            <div v-if="blog.descriptionHtml" class="prose prose-slate dark:prose-invert max-w-none" v-html="blog.descriptionHtml" />
                        </header>
                        <!-- No landing content -->
                    </main>
                </div>
                <div v-else-if="sidebarWidth > 0 && (props.sidebar ?? 0) > 0" class="flex items-start gap-8">
                    <main :style="{ width: 100 - sidebarWidth + '%', flex: '1 1 ' + (100 - sidebarWidth) + '%' }" class="min-w-0 flex-1">
                        <header class="mb-4">
                            <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-400">{{ blog.name }}</h1>
                            <div v-if="blog.descriptionHtml" class="prose prose-slate dark:prose-invert max-w-none" v-html="blog.descriptionHtml" />
                        </header>
                        <!-- No landing content -->
                    </main>
                    <aside :style="{ width: sidebarWidth + '%', flex: '0 0 ' + sidebarWidth + '%' }">
                        <BlogPostsList :blogSlug="blog.slug" :pagination="pagination" :posts="posts" />
                    </aside>
                </div>
                <div v-else>
                    <BlogPostsList :blogSlug="blog.slug" :pagination="pagination" :posts="posts" />
                </div>
            </template>

            <!-- Navigation at bottom -->
            <BlogPostNav :navigation="navigation" />
        </div>
    </div>
</template>
