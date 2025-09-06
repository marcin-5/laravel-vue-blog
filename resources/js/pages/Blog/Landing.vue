<script lang="ts" setup>
import ThemeToggle from '@/components/ThemeToggle.vue';
import { ensureNamespace } from '@/i18n';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import BlogPostsList from '../../components/BlogPostsList.vue';

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
}>();

const hasLanding = !!props.landingHtml;

const { t, locale } = useI18n();
// Ensure 'landing' namespace is available for nav labels (SSR-safe with Suspense)
await ensureNamespace(locale.value, 'landing');

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
        <header class="w-full px-4 pt-4 text-sm sm:px-6 lg:px-8">
            <nav class="mx-auto flex w-full max-w-[1024px] items-center justify-end gap-4">
                <template v-if="$page.props.auth.user">
                    <Link
                        :href="route('dashboard')"
                        class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                    >
                        {{ t('landing.nav.dashboard') }}
                    </Link>
                    <Link
                        :href="route('logout')"
                        as="button"
                        class="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                        method="post"
                    >
                        {{ t('landing.nav.logout') }}
                    </Link>
                </template>
                <template v-else>
                    <Link
                        :href="route('login')"
                        class="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                    >
                        {{ t('landing.nav.login') }}
                    </Link>
                    <Link
                        :href="route('register')"
                        class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                    >
                        {{ t('landing.nav.register') }}
                    </Link>
                </template>
                <ThemeToggle />
            </nav>
        </header>

        <div class="mx-auto w-full max-w-[1024px] p-4">
            <header v-if="sidebarWidth === 0" class="mb-4">
                <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-400">{{ blog.name }}</h1>
                <div v-if="blog.descriptionHtml" class="prose prose-slate max-w-none dark:prose-invert" v-html="blog.descriptionHtml" />
            </header>

            <template v-if="hasLanding">
                <div v-if="sidebarWidth > 0 && (props.sidebar ?? 0) < 0" class="flex items-start gap-8">
                    <aside :style="{ width: sidebarWidth + '%', flex: '0 0 ' + sidebarWidth + '%' }">
                        <BlogPostsList :blogSlug="blog.slug" :posts="posts" :pagination="pagination" />
                    </aside>
                    <main class="min-w-0 flex-1" :style="{ width: (100 - sidebarWidth) + '%', flex: '1 1 ' + (100 - sidebarWidth) + '%' }">
                        <header class="mb-4">
                            <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-400">{{ blog.name }}</h1>
                            <div v-if="blog.descriptionHtml" class="prose prose-slate max-w-none dark:prose-invert" v-html="blog.descriptionHtml" />
                        </header>
                        <div class="prose max-w-none" v-html="landingHtml" />
                    </main>
                </div>

                <div v-else-if="sidebarWidth > 0 && (props.sidebar ?? 0) > 0" class="flex items-start gap-8">
                    <main class="min-w-0 flex-1" :style="{ width: (100 - sidebarWidth) + '%', flex: '1 1 ' + (100 - sidebarWidth) + '%' }">
                        <header class="mb-4">
                            <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-400">{{ blog.name }}</h1>
                            <div v-if="blog.descriptionHtml" class="prose prose-slate max-w-none dark:prose-invert" v-html="blog.descriptionHtml" />
                        </header>
                        <div class="prose max-w-none" v-html="landingHtml" />
                    </main>
                    <aside :style="{ width: sidebarWidth + '%', flex: '0 0 ' + sidebarWidth + '%' }">
                        <BlogPostsList :blogSlug="blog.slug" :posts="posts" :pagination="pagination" />
                    </aside>
                </div>

                <div v-else>
                    <main class="min-w-0 flex-1">
                        <div class="prose max-w-none" v-html="landingHtml" />
                    </main>
                    <BlogPostsList :blogSlug="blog.slug" :posts="posts" :pagination="pagination" class="mt-6" />
                </div>
            </template>

            <template v-else>
                <div v-if="sidebarWidth > 0 && (props.sidebar ?? 0) < 0" class="flex items-start gap-8">
                    <aside :style="{ width: sidebarWidth + '%', flex: '0 0 ' + sidebarWidth + '%' }">
                        <BlogPostsList :blogSlug="blog.slug" :posts="posts" :pagination="pagination" />
                    </aside>
                    <main class="min-w-0 flex-1" :style="{ width: (100 - sidebarWidth) + '%', flex: '1 1 ' + (100 - sidebarWidth) + '%' }">
                        <header class="mb-4">
                            <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-400">{{ blog.name }}</h1>
                            <div v-if="blog.descriptionHtml" class="prose prose-slate max-w-none dark:prose-invert" v-html="blog.descriptionHtml" />
                        </header>
                        <!-- No landing content -->
                    </main>
                </div>
                <div v-else-if="sidebarWidth > 0 && (props.sidebar ?? 0) > 0" class="flex items-start gap-8">
                    <main class="min-w-0 flex-1" :style="{ width: (100 - sidebarWidth) + '%', flex: '1 1 ' + (100 - sidebarWidth) + '%' }">
                        <header class="mb-4">
                            <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-400">{{ blog.name }}</h1>
                            <div v-if="blog.descriptionHtml" class="prose prose-slate max-w-none dark:prose-invert" v-html="blog.descriptionHtml" />
                        </header>
                        <!-- No landing content -->
                    </main>
                    <aside :style="{ width: sidebarWidth + '%', flex: '0 0 ' + sidebarWidth + '%' }">
                        <BlogPostsList :blogSlug="blog.slug" :posts="posts" :pagination="pagination" />
                    </aside>
                </div>
                <div v-else>
                    <BlogPostsList :blogSlug="blog.slug" :posts="posts" :pagination="pagination" />
                </div>
            </template>
        </div>
    </div>
</template>
