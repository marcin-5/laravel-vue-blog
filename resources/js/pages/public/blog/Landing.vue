<script lang="ts" setup>
import BlogFooter from '@/components/blog/BlogFooter.vue';
import BlogHeader from '@/components/blog/BlogHeader.vue';
import BlogPostNav from '@/components/blog/BlogPostNav.vue';
import BlogPostsList from '@/components/blog/BlogPostsList.vue';
import BorderDivider from '@/components/blog/BorderDivider.vue';
import PublicNavbar from '@/components/PublicNavbar.vue';
import { useBlogTheme } from '@/composables/useBlogTheme';
import { useSidebarLayout } from '@/composables/useSidebarLayout';
import { SIDEBAR_MAX_WIDTH, SIDEBAR_MIN_WIDTH } from '@/types/blog';
import type { Blog, Navigation, Pagination, PostItem } from '@/types/blog.types';
import { hasContent, selectRandomMotto } from '@/utils/stringUtils';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    blog: Blog;
    landingHtml: string;
    footerHtml?: string;
    posts: PostItem[];
    pagination?: Pagination | null;
    // numeric sidebar value (-50..50).
    sidebar?: number;
    metaDescription: string;
    navigation?: Navigation;
    locale?: string;
    viewStats: {
        anonymous: number;
        bots: number;
        registered: number;
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

// Sidebar layout calculations
const {
    hasSidebar: hasSidebarLayout,
    asideStyle,
    mainStyle,
    asideOrderClass,
    mainOrderClass,
    navbarMaxWidth,
} = useSidebarLayout({
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
        <div
            :class="[
                'mx-auto w-full p-4 sm:px-12 md:px-16',
                hasSidebarLayout ? 'max-w-screen-lg xl:max-w-screen-xl 2xl:max-w-screen-2xl' : 'max-w-screen-lg',
            ]"
        >
            <BorderDivider class="mb-4" />

            <!-- Layout without sidebar -->
            <template v-if="!hasSidebarLayout">
                <BlogHeader :blog="blog" :displayedMotto="displayedMotto" :viewStats="viewStats" />
                <BorderDivider class="mb-8" />
                <main v-if="hasLandingContent" class="min-w-0 flex-1">
                    <div class="prose max-w-none text-primary" v-html="landingHtml" />
                </main>
                <BorderDivider class="my-4" />
                <BlogPostsList
                    :blogId="blog.id"
                    :blogSlug="blog.slug"
                    :class="{ 'mt-6': hasLandingContent }"
                    :pagination="pagination"
                    :posts="posts"
                />
            </template>

            <!-- Layout with sidebar (hidden on <xl, visible from xl+) -->
            <template v-else>
                <!-- Mobile/tablet layout (<xl): no sidebar -->
                <div class="xl:hidden">
                    <BlogHeader :blog="blog" :displayedMotto="displayedMotto" :viewStats="viewStats" />
                    <BorderDivider class="mb-8" />
                    <main v-if="hasLandingContent" class="min-w-0 flex-1">
                        <div class="prose max-w-none" v-html="landingHtml" />
                    </main>
                    <BlogPostsList
                        :blogId="blog.id"
                        :blogSlug="blog.slug"
                        :class="{ 'mt-6': hasLandingContent }"
                        :pagination="pagination"
                        :posts="posts"
                    />
                </div>

                <!-- Desktop layout (xl+): with sidebar -->
                <div class="hidden items-start gap-8 xl:flex">
                    <aside :class="asideOrderClass" :style="asideStyle">
                        <BlogPostsList :blogId="blog.id" :blogSlug="blog.slug" :pagination="pagination" :posts="posts" />
                    </aside>
                    <main :class="['min-w-0 flex-1', mainOrderClass]" :style="mainStyle">
                        <BlogHeader :blog="blog" :displayedMotto="displayedMotto" :viewStats="viewStats" />
                        <div v-if="hasLandingContent" class="prose max-w-none" v-html="landingHtml" />
                    </main>
                </div>
            </template>

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
