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
    description?: string | null;
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
    sidebarPosition: 'left' | 'right' | 'none';
}>();

const hasLanding = !!props.landingHtml;

const { t, locale } = useI18n();
// Ensure 'landing' namespace is available for nav labels (SSR-safe with Suspense)
await ensureNamespace(locale.value, 'landing');
</script>

<template>
    <Head :title="blog.name">
        <meta :content="blog.description || blog.name" name="description" />
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
            <header class="mb-4">
                <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-400">{{ blog.name }}</h1>
                <p v-if="blog.description" class="text-slate-800 dark:text-gray-400">{{ blog.description }}</p>
            </header>

            <template v-if="hasLanding">
                <div v-if="sidebarPosition === 'left'" class="flex items-start gap-8">
                    <aside class="w-[280px]">
                        <BlogPostsList :blogSlug="blog.slug" :posts="posts" />
                    </aside>
                    <main class="min-w-0 flex-1">
                        <div class="prose max-w-none" v-html="landingHtml" />
                    </main>
                </div>

                <div v-else-if="sidebarPosition === 'right'" class="flex items-start gap-8">
                    <main class="min-w-0 flex-1">
                        <div class="prose max-w-none" v-html="landingHtml" />
                    </main>
                    <aside class="w-[280px]">
                        <BlogPostsList :blogSlug="blog.slug" :posts="posts" />
                    </aside>
                </div>

                <div v-else>
                    <main class="min-w-0 flex-1">
                        <div class="prose max-w-none" v-html="landingHtml" />
                    </main>
                    <BlogPostsList :blogSlug="blog.slug" :posts="posts" class="mt-6" />
                </div>
            </template>

            <template v-else>
                <BlogPostsList :blogSlug="blog.slug" :posts="posts" />
            </template>
        </div>
    </div>
</template>
