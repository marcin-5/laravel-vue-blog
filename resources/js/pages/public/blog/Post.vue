<script lang="ts" setup>
import BlogPostNav from '@/components/blog/BlogPostNav.vue';
import BlogPostsList from '@/components/blog/BlogPostsList.vue';
import BorderDivider from '@/components/blog/BorderDivider.vue';
import PostContent from '@/components/blog/PostContent.vue';
import PostExtensions from '@/components/blog/PostExtensions.vue';
import PublicNavbar from '@/components/PublicNavbar.vue';
import { useBlogTheme } from '@/composables/useBlogTheme';
import { useSidebarLayout } from '@/composables/useSidebarLayout';
import type { SEO } from '@/types';
import { SIDEBAR_MAX_WIDTH, SIDEBAR_MIN_WIDTH } from '@/types/blog';
import type { Blog, Navigation, Pagination, PostDetails, PostItem } from '@/types/blog.types';
import { formatDate, shouldShowUpdatedDate } from '@/utils/dateUtils';
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
const { hasSidebar, asideStyle, mainStyle, asideOrderClass, mainOrderClass, navbarMaxWidth } = useSidebarLayout({
    sidebar: props.sidebar,
    minPercent: SIDEBAR_MIN_WIDTH,
    maxPercent: SIDEBAR_MAX_WIDTH,
});

// Theme handling
const { mergedThemeStyle } = useBlogTheme(computed(() => props.blog.theme));
</script>

<template>
    <div :style="mergedThemeStyle" class="flex min-h-screen flex-col bg-background text-foreground antialiased">
        <PublicNavbar :maxWidth="navbarMaxWidth" />

        <div
            :class="[
                'mx-auto w-full p-4 sm:px-12 md:px-16',
                hasSidebar ? 'max-w-screen-lg xl:max-w-screen-xl 2xl:max-w-screen-2xl' : 'max-w-screen-lg',
            ]"
        >
            <BorderDivider class="mb-4" />

            <header :style="{ fontFamily: 'var(--blog-header-font)', fontSize: 'calc(1.5rem * var(--blog-header-scale))' }" class="mb-4">
                <h1 class="font-[inherit] text-[1em] leading-tight font-bold text-foreground">{{ post.title }}</h1>
                <div class="my-2 inline-flex items-center gap-x-5 text-sm font-medium text-muted-foreground">
                    <p v-if="post.published_at" class="italic">{{ publishedLabel }} {{ post.published_at }}</p>
                    <span>
                        Odsłony: {{ viewStats.total.toLocaleString() }}
                        <template v-if="viewStats.unique !== undefined"> (unikalne: {{ Number(viewStats.unique).toLocaleString() }}) </template>
                    </span>
                </div>
                <p v-if="showUpdated" class="-mt-1 mb-2 text-xs text-muted-foreground italic">{{ updatedLabel }} {{ formattedUpdatedDate }}</p>
                <p v-if="post.author" :style="{ fontFamily: 'var(--blog-footer-font)' }" class="text-lg text-foreground">
                    {{ authorLabel }}
                    <a :href="`mailto:${post.author_email}`">{{ post.author }}</a>
                </p>
            </header>

            <!-- Add separation line under header when no sidebar -->
            <BorderDivider v-if="!hasSidebar" class="mb-8" />

            <!-- Layout with sidebar (hidden on <xl, visible from xl+) -->
            <template v-if="hasSidebar">
                <!-- Mobile/tablet layout (<xl): no sidebar -->
                <div class="xl:hidden">
                    <PostContent :author="post.author" :content="post.contentHtml" />
                    <PostExtensions :extensions="post.extensions || []" />
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
                        <PostExtensions :extensions="post.extensions || []" />
                    </div>
                </div>
            </template>

            <!-- No sidebar layout -->
            <div v-else>
                <PostContent :author="post.author" :content="post.contentHtml" />
                <PostExtensions :extensions="post.extensions || []" />
                <BorderDivider class="mt-12 mb-4" />
                <BlogPostsList :blogId="blog.id" :blogSlug="blog.slug" :pagination="pagination" :posts="posts" class="mt-6" />
            </div>

            <!-- Post Navigation at bottom -->
            <BlogPostNav :navigation="navigation" />
        </div>
    </div>
</template>
