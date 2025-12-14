<script lang="ts" setup>
import BlogPostNav from '@/components/blog/BlogPostNav.vue';
import BlogPostsList from '@/components/blog/BlogPostsList.vue';
import BorderDivider from '@/components/blog/BorderDivider.vue';
import PostContent from '@/components/blog/PostContent.vue';
import PublicNavbar from '@/components/PublicNavbar.vue';
import { useSidebarLayout } from '@/composables/useSidebarLayout';
import type { SEO } from '@/types';
import { SIDEBAR_MAX_WIDTH, SIDEBAR_MIN_WIDTH } from '@/types/blog';
import type { Blog, Navigation, Pagination, PostDetails, PostItem } from '@/types/blog.types';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    blog: Blog;
    post: PostDetails;
    posts: PostItem[];
    sidebar?: number; // numeric sidebar value (-50..50). Sign indicates side: negative -> left, positive -> right, 0 -> no sidebar
    pagination?: Pagination | null;
    navigation?: Navigation;
    locale?: string;
    seo?: SEO;
    viewStats: {
        total: number;
        unique?: number;
    };
}>();

const { t } = useI18n();

// Constants
const ONE_DAY_MS = 24 * 60 * 60 * 1000;

// Helper functions
function isValidDate(dateString: string | null | undefined): boolean {
    if (!dateString) return false;
    const date = new Date(dateString);
    return !isNaN(date.getTime());
}

function formatDate(dateString: string | null | undefined, locale?: string): string {
    if (!dateString) return '';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return String(dateString);

    try {
        return new Intl.DateTimeFormat(locale || undefined, {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        }).format(date);
    } catch {
        return date.toLocaleDateString();
    }
}

function shouldShowUpdatedDate(publishedTime: string | null | undefined, modifiedTime: string | null | undefined): boolean {
    if (!isValidDate(publishedTime) || !isValidDate(modifiedTime)) return false;

    const published = new Date(publishedTime!);
    const modified = new Date(modifiedTime!);

    return Math.abs(modified.getTime() - published.getTime()) > ONE_DAY_MS;
}

// Internationalization
const authorLabel = computed(() => t('blog.post.author', ''));
const publishedLabel = computed(() => t('blog.post.published', 'Published:'));
const updatedLabel = computed(() => t('blog.post.updated', 'Updated:'));

const postPublishedTime = computed(() => props.seo?.publishedTime || null);
const postModifiedTime = computed(() => props.seo?.modifiedTime || null);

// Date display computed properties
const showUpdated = computed(() => shouldShowUpdatedDate(postPublishedTime.value, postModifiedTime.value));

const formattedUpdatedDate = computed(() => formatDate(postModifiedTime.value, props.locale));

// Sidebar layout
const { hasSidebar, isLeftSidebar, isRightSidebar, asideStyle, mainStyle } = useSidebarLayout({
    sidebar: props.sidebar,
    minPercent: SIDEBAR_MIN_WIDTH,
    maxPercent: SIDEBAR_MAX_WIDTH,
});

// Navbar max-width class based on sidebar layout
const navbarMaxWidth = computed(() => (hasSidebar.value ? 'max-w-screen-lg xl:max-w-screen-xl 2xl:max-w-screen-2xl' : 'max-w-[1024px]'));
</script>

<template>
    <div class="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a]">
        <PublicNavbar :maxWidth="navbarMaxWidth" />

        <div
            :class="[
                'mx-auto w-full p-4 sm:px-12 md:px-16',
                hasSidebar ? 'max-w-screen-lg xl:max-w-screen-xl 2xl:max-w-screen-2xl' : 'max-w-[1024px]',
            ]"
        >
            <BorderDivider class="mb-4" />

            <header class="mb-4">
                <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-400">{{ post.title }}</h1>
                <div class="my-2 inline-flex items-center gap-x-5 text-sm font-medium text-gray-800 dark:text-gray-300">
                    <p v-if="post.published_at" class="italic">{{ publishedLabel }} {{ post.published_at }}</p>
                    <span>
                        Odsłony: {{ viewStats.total.toLocaleString() }}
                        <template v-if="viewStats.unique !== undefined"> (unikalne: {{ Number(viewStats.unique).toLocaleString() }}) </template>
                    </span>
                </div>
                <p v-if="showUpdated" class="-mt-1 mb-2 text-xs text-gray-600 italic dark:text-gray-400">
                    {{ updatedLabel }} {{ formattedUpdatedDate }}
                </p>
                <p v-if="post.author" class="text-md text-gray-900 dark:text-gray-200">
                    {{ authorLabel }}
                    <a :href="`mailto:${post.author_email}`">{{ post.author }}</a>
                </p>
            </header>

            <!-- Add separation line under header when no sidebar -->
            <BorderDivider v-if="!hasSidebar" class="mb-8" />

            <!-- Left sidebar layout -->
            <template v-if="isLeftSidebar">
                <!-- Mobile/tablet layout (<xl): no sidebar -->
                <div class="xl:hidden">
                    <PostContent :author="post.author" :content="post.contentHtml" />
                    <BorderDivider class="mt-12 mb-4" />
                    <BlogPostsList :blogSlug="blog.slug" :pagination="pagination" :posts="posts" class="mt-6" />
                </div>

                <!-- Desktop layout (xl+): with left sidebar -->
                <div class="hidden items-start gap-8 xl:flex">
                    <aside :style="asideStyle">
                        <BlogPostsList :blogSlug="blog.slug" :pagination="pagination" :posts="posts" />
                    </aside>
                    <div :style="mainStyle" class="min-w-0 flex-1">
                        <PostContent :author="post.author" :content="post.contentHtml" />
                    </div>
                </div>
            </template>

            <!-- Right sidebar layout -->
            <template v-else-if="isRightSidebar">
                <!-- Mobile/tablet layout (<xl): no sidebar -->
                <div class="xl:hidden">
                    <PostContent :author="post.author" :content="post.contentHtml" />
                    <BorderDivider class="mt-12 mb-4" />
                    <BlogPostsList :blogSlug="blog.slug" :pagination="pagination" :posts="posts" class="mt-6" />
                </div>

                <!-- Desktop layout (xl+): with right sidebar -->
                <div class="hidden items-start gap-8 xl:flex">
                    <div :style="mainStyle" class="min-w-0 flex-1">
                        <PostContent :author="post.author" :content="post.contentHtml" />
                    </div>
                    <aside :style="asideStyle">
                        <BlogPostsList :blogSlug="blog.slug" :pagination="pagination" :posts="posts" />
                    </aside>
                </div>
            </template>

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
