<script lang="ts" setup>
import BlogFooter from '@/components/blog/BlogFooter.vue';
import BlogHeader from '@/components/blog/BlogHeader.vue';
import BlogPostNav from '@/components/blog/BlogPostNav.vue';
import BlogPostsList from '@/components/blog/BlogPostsList.vue';
import BorderDivider from '@/components/blog/BorderDivider.vue';
import PublicNavbar from '@/components/PublicNavbar.vue';
import { useSidebarLayout } from '@/composables/useSidebarLayout';
import { hasContent } from '@/lib/utils';
import { SIDEBAR_MAX_WIDTH, SIDEBAR_MIN_WIDTH } from '@/types/blog';
import type { Blog, Navigation, Pagination, PostItem } from '@/types/blog.types';
import { Link } from '@inertiajs/vue3';
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
        total: number;
        unique?: number;
    };
}>();

// Content availability checks
const hasLandingContent = computed(() => hasContent(props.landingHtml));
const hasFooterContent = computed(() => hasContent(props.footerHtml));

// Motto selection
function selectRandomMottoFromList(mottoText: string | null | undefined): string | null {
    if (!mottoText) return null;

    const mottoList = mottoText.split('\n\n').filter((motto) => motto.trim());
    if (mottoList.length === 0) return null;

    const randomIndex = Math.floor(Math.random() * mottoList.length);
    return mottoList[randomIndex].trim();
}

const displayedMotto = selectRandomMottoFromList(props.blog.motto);

// Sidebar layout calculations
const {
    hasSidebar: hasSidebarLayout,
    asideStyle,
    mainStyle,
    asideOrderClass,
    mainOrderClass,
} = useSidebarLayout({
    sidebar: props.sidebar,
    minPercent: SIDEBAR_MIN_WIDTH,
    maxPercent: SIDEBAR_MAX_WIDTH,
});

// Navbar max-width class based on sidebar layout
const navbarMaxWidth = computed(() => (hasSidebarLayout.value ? 'max-w-screen-lg xl:max-w-screen-xl 2xl:max-w-screen-2xl' : 'max-w-screen-lg'));
</script>

<template>
    <div class="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a]">
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
                    <div class="prose max-w-none" v-html="landingHtml" />
                </main>
                <BlogPostsList :blogSlug="blog.slug" :class="{ 'mt-6': hasLandingContent }" :pagination="pagination" :posts="posts" />
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
                    <BlogPostsList :blogSlug="blog.slug" :class="{ 'mt-6': hasLandingContent }" :pagination="pagination" :posts="posts" />
                </div>

                <!-- Desktop layout (xl+): with sidebar -->
                <div class="hidden items-start gap-8 xl:flex">
                    <aside :class="asideOrderClass" :style="asideStyle">
                        <BlogPostsList :blogSlug="blog.slug" :pagination="pagination" :posts="posts" />
                    </aside>
                    <main :class="['min-w-0 flex-1', mainOrderClass]" :style="mainStyle">
                        <BlogHeader :blog="blog" :displayedMotto="displayedMotto" :viewStats="viewStats" />
                        <div v-if="hasLandingContent" class="prose max-w-none" v-html="landingHtml" />
                    </main>
                </div>
            </template>

            <!-- Navigation at bottom -->
            <div class="mt-8 flex justify-center">
                <Link
                    :href="route('newsletter.index', { blog_id: blog.id })"
                    class="text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100"
                >
                    Zapisz się do newslettera
                </Link>
            </div>

            <BlogPostNav :navigation="navigation" />

            <!-- Footer (optional) -->
            <template v-if="hasFooterContent">
                <BorderDivider class="my-4" />
                <BlogFooter :html="footerHtml || ''" />
            </template>
        </div>
    </div>
</template>
