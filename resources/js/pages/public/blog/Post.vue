<script lang="ts" setup>
import BlogPostNav from '@/components/blog/BlogPostNav.vue';
import BlogPostsList from '@/components/blog/BlogPostsList.vue';
import BorderDivider from '@/components/blog/BorderDivider.vue';
import PostContent from '@/components/blog/PostContent.vue';
import PostExtensions from '@/components/blog/PostExtensions.vue';
import PostHeader from '@/components/blog/PostHeader.vue';
import PublicNavbar from '@/components/PublicNavbar.vue';
import { useBlogTheme } from '@/composables/useBlogTheme';
import { useSidebarLayout } from '@/composables/useSidebarLayout';
import type { SEO } from '@/types';
import { SIDEBAR_MAX_WIDTH, SIDEBAR_MIN_WIDTH } from '@/types/blog';
import type { Blog, Navigation, Pagination, PostDetails, PostItem } from '@/types/blog.types';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

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

// Internationalization
const postPublishedTime = computed(() => props.seo?.publishedTime || null);
const postModifiedTime = computed(() => props.seo?.modifiedTime || null);

// Sidebar layout
const { hasSidebar, asideStyle, mainStyle, asideOrderClass, mainOrderClass, navbarMaxWidth } = useSidebarLayout({
    sidebar: props.sidebar,
    minPercent: SIDEBAR_MIN_WIDTH,
    maxPercent: SIDEBAR_MAX_WIDTH,
});

// Theme handling
const { mergedThemeStyle } = useBlogTheme(computed(() => props.blog.theme));
</script>

<template>
    <Head v-if="seo?.title" :title="seo.title" />
    <div :style="mergedThemeStyle" class="flex min-h-screen flex-col bg-background text-primary antialiased">
        <PublicNavbar :maxWidth="navbarMaxWidth" />

        <div :class="['mx-auto w-full p-4 sm:px-12 md:px-16', hasSidebar ? 'max-w-5xl xl:max-w-7xl 2xl:max-w-screen-2xl' : 'max-w-5xl']">
            <BorderDivider class="mb-4" />

            <PostHeader :locale="locale" :modifiedTime="postModifiedTime" :post="post" :publishedTime="postPublishedTime" :viewStats="viewStats" />

            <!-- Add separation line under header when no sidebar -->
            <BorderDivider v-if="!hasSidebar" class="mb-8" />

            <!-- Layout with sidebar (hidden on <xl, visible from xl+) -->
            <template v-if="hasSidebar">
                <!-- Mobile/tablet layout (<xl): no sidebar -->
                <div class="xl:hidden">
                    <PostContent :author="post.author" :content="post.contentHtml" />
                    <PostExtensions :extensions="post.extensions || []" :theme="mergedThemeStyle" />
                    <BorderDivider class="mt-12 mb-4" />
                    <BlogPostsList :blogId="blog.id" :blogSlug="blog.slug" :pagination="pagination" :posts="posts" class="mt-6" />
                </div>

                <!-- Desktop layout (xl+): with sidebar using order classes -->
                <div class="hidden items-start gap-8 xl:flex">
                    <aside :class="asideOrderClass" :style="asideStyle">
                        <BlogPostsList :blogId="blog.id" :blogSlug="blog.slug" :pagination="pagination" :posts="posts" />
                    </aside>
                    <div :class="['min-w-0 flex-1', mainOrderClass]" :style="mainStyle">
                        <PostContent :author="post.author" :content="post.contentHtml" />
                        <PostExtensions :extensions="post.extensions || []" :theme="mergedThemeStyle" />
                    </div>
                </div>
            </template>

            <!-- No sidebar layout -->
            <div v-else>
                <PostContent :author="post.author" :content="post.contentHtml" />
                <PostExtensions :extensions="post.extensions || []" :theme="mergedThemeStyle" />
                <BorderDivider class="mt-12 mb-4" />
                <BlogPostsList :blogId="blog.id" :blogSlug="blog.slug" :pagination="pagination" :posts="posts" class="mt-6" />
            </div>

            <!-- Post Navigation at bottom -->
            <BlogPostNav :navigation="navigation" />
        </div>
    </div>
</template>
