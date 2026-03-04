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
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    blog: Blog;
    post: PostDetails;
    posts: PostItem[];
    sidebar?: number;
    pagination?: Pagination | null;
    navigation?: Navigation;
    locale?: string;
    seo?: SEO;
    viewStats: {
        anonymous: number;
        bots: number;
        consented: number;
    } | null;
}>();

const { t } = useI18n();

// SEO timestamps
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

// Derived post state
const isListed = computed(() => props.post.visibility !== 'unlisted');
const postExtensionsList = computed(() => props.post.extensions || []);

// Shared prop objects to avoid template repetition
const postHeaderProps = computed(() => ({
    locale: props.locale,
    modifiedTime: postModifiedTime.value,
    post: props.post,
    publishedTime: postPublishedTime.value,
    viewStats: props.viewStats,
}));

const blogPostsListProps = computed(() => ({
    blogId: props.blog.id,
    blogSlug: props.blog.slug,
    pagination: props.pagination,
    posts: props.posts,
}));

// Navigation
const navigateBack = () => {
    router.visit(props.navigation?.landingUrl ?? `/${props.blog.slug}`);
};
</script>

<template>
    <Head v-if="seo?.title" :title="seo.title" />
    <div :style="mergedThemeStyle" class="flex min-h-screen flex-col bg-background text-primary antialiased">
        <PublicNavbar :maxWidth="navbarMaxWidth" />
        <div :class="['mx-auto w-full p-4 sm:px-12 md:px-16', hasSidebar ? 'max-w-5xl xl:max-w-7xl 2xl:max-w-screen-2xl' : 'max-w-5xl']">
            <BorderDivider class="mb-4" />

            <!-- Layout with sidebar -->
            <template v-if="hasSidebar">
                <!-- Mobile/tablet layout (<xl): no sidebar -->
                <div class="xl:hidden">
                    <PostHeader v-bind="postHeaderProps" />
                    <PostContent :author="post.author" :content="post.contentHtml" />
                    <PostExtensions :extensions="postExtensionsList" :theme="mergedThemeStyle" />
                    <BorderDivider class="mt-12 mb-4" />
                    <BlogPostsList v-if="isListed" class="mt-6" v-bind="blogPostsListProps" />
                </div>

                <!-- Desktop layout (xl+): with sidebar -->
                <div class="hidden items-start gap-8 xl:flex">
                    <aside :class="asideOrderClass" :style="asideStyle">
                        <BlogPostsList v-if="isListed" v-bind="blogPostsListProps" />
                    </aside>
                    <div :class="['min-w-0 flex-1', mainOrderClass]" :style="mainStyle">
                        <PostHeader v-bind="postHeaderProps" />
                        <PostContent :author="post.author" :content="post.contentHtml" />
                        <PostExtensions :extensions="postExtensionsList" :theme="mergedThemeStyle" />
                    </div>
                </div>
            </template>

            <!-- No sidebar layout -->
            <div v-else>
                <PostHeader v-bind="postHeaderProps" />
                <BorderDivider class="mb-8" />
                <PostContent :author="post.author" :content="post.contentHtml" />
                <PostExtensions :extensions="postExtensionsList" :theme="mergedThemeStyle" />
                <BorderDivider class="mt-12 mb-4" />
                <BlogPostsList v-if="isListed" class="mt-6" v-bind="blogPostsListProps" />
            </div>

            <!-- Post Navigation -->
            <BlogPostNav v-if="isListed" :navigation="navigation" />
            <div v-else class="flex items-center">
                <button
                    class="inline-flex items-center rounded-sm border border-border bg-card px-3 py-2 text-sm text-primary transition-colors hover:bg-secondary"
                    @click="navigateBack"
                >
                    <ArrowLeft class="mr-2 size-4" />
                    <span>{{ t('blog.post_nav.back') }}</span>
                </button>
            </div>
        </div>
    </div>
</template>
