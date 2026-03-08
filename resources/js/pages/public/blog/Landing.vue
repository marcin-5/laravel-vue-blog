<script lang="ts" setup>
import BlogFooter from '@/components/blog/BlogFooter.vue';
import BlogHeader from '@/components/blog/BlogHeader.vue';
import BlogPostNav from '@/components/blog/BlogPostNav.vue';
import BlogPostsList from '@/components/blog/BlogPostsList.vue';
import BorderDivider from '@/components/blog/BorderDivider.vue';
import ScrollToPostsLink from '@/components/blog/ScrollToPostsLink.vue';
import PublicNavbar from '@/components/PublicNavbar.vue';
import { useBlogTheme } from '@/composables/useBlogTheme';
import { useSidebarLayout } from '@/composables/useSidebarLayout';
import { SIDEBAR_MAX_WIDTH, SIDEBAR_MIN_WIDTH } from '@/types/blog';
import type { Blog, Navigation, Pagination, PostItem } from '@/types/blog.types';
import { handleContentClick } from '@/utils/domUtils';
import { hasContent, selectRandomMotto } from '@/utils/stringUtils';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    blog: Blog;
    landingHtml: string;
    footerHtml?: string;
    posts: PostItem[];
    pagination?: Pagination | null;
    /** Numeric sidebar value (-50..50). */
    sidebar?: number;
    metaDescription: string;
    navigation?: Navigation;
    locale?: string;
    viewStats: {
        anonymous: number;
        bots: number;
        consented: number;
    } | null;
    seo?: {
        title: string;
    };
}>();

// Content availability checks
const hasLandingContent = computed(() => hasContent(props.landingHtml));
const hasFooterContent = computed(() => hasContent(props.footerHtml));

// Motto selection
const displayedMotto = selectRandomMotto(props.blog.motto);

// Sidebar layout
const { hasSidebar, asideStyle, mainStyle, asideOrderClass, mainOrderClass, navbarMaxWidth } = useSidebarLayout({
    sidebar: props.sidebar,
    minPercent: SIDEBAR_MIN_WIDTH,
    maxPercent: SIDEBAR_MAX_WIDTH,
});

// Theme handling
const { mergedThemeStyle } = useBlogTheme(computed(() => props.blog.theme));

// Derived template classes
const containerClass = computed(() => [
    'mx-auto w-full p-4 sm:px-12 md:px-16',
    hasSidebar.value ? 'max-w-screen-lg xl:max-w-screen-xl 2xl:max-w-screen-2xl' : 'max-w-screen-lg',
]);

const postsListSpacingClass = computed(() => (hasLandingContent.value ? 'mt-6' : ''));
</script>

<template>
    <Head v-if="seo?.title" :title="seo.title" />

    <div :style="mergedThemeStyle" class="flex min-h-screen flex-col bg-background text-primary antialiased">
        <PublicNavbar :maxWidth="navbarMaxWidth" />

        <div :class="containerClass">
            <BorderDivider class="mb-4" />

            <!-- Stacked layout: used when there is no sidebar at all,
            AND as the mobile/tablet (<xl) fallback when sidebar is configured. -->
            <div :class="{ 'xl:hidden': hasSidebar }">
                <BlogHeader :blog="blog" :displayedMotto="displayedMotto" :viewStats="viewStats" />
                <ScrollToPostsLink />

                <main v-if="hasLandingContent" class="min-w-0 flex-1">
                    <div class="prose max-w-none text-primary" @click="handleContentClick" v-html="landingHtml" />
                </main>

                <BorderDivider v-if="!hasSidebar" class="my-4" />

                <BlogPostsList
                    id="posts-list"
                    :blogId="blog.id"
                    :blogSlug="blog.slug"
                    :class="postsListSpacingClass"
                    :pagination="pagination"
                    :posts="posts"
                />
            </div>

            <!-- Desktop sidebar layout (xl+), only rendered when sidebar is configured -->
            <div v-if="hasSidebar" class="hidden items-start gap-8 xl:flex">
                <aside :class="asideOrderClass" :style="asideStyle">
                    <BlogPostsList :blogId="blog.id" :blogSlug="blog.slug" :pagination="pagination" :posts="posts" />
                </aside>

                <main :class="['min-w-0 flex-1', mainOrderClass]" :style="mainStyle">
                    <BlogHeader :blog="blog" :displayedMotto="displayedMotto" :viewStats="viewStats" />
                    <div v-if="hasLandingContent" class="prose max-w-none" @click="handleContentClick" v-html="landingHtml" />
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
