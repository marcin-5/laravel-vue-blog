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
import { hasContent } from '@/utils/stringUtils';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    group: {
        id: number;
        name: string;
        slug: string;
        content: string | null;
        footer: string | null;
    };
    posts: PostItem[];
    pagination: Pagination;
    theme: any;
    sidebar: number;
    navigation: Navigation;
    viewStats: {
        total: number;
        unique?: number;
    };
}>();

// Map group to Blog type for components compatibility
const blogData = computed<Blog>(() => ({
    id: props.group.id,
    name: props.group.name,
    slug: props.group.slug,
    motto: null,
    theme: props.theme,
}));

const { t } = useI18n();

// Content availability checks
const hasLandingContent = computed(() => hasContent(props.group.content));
const hasFooterContent = computed(() => hasContent(props.group.footer));

const postsListTitle = computed(() => t('blog.posts_list.title'));

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
const { mergedThemeStyle } = useBlogTheme(computed(() => props.theme));
</script>

<template>
    <Head :title="group.name" />
    <div :style="mergedThemeStyle" class="flex min-h-screen flex-col bg-background text-foreground antialiased">
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
                <BlogHeader :blog="blogData" :displayedMotto="null" :viewStats="viewStats" />
                <BorderDivider class="mb-8" />
                <main v-if="hasLandingContent" class="min-w-0 flex-1">
                    <div class="prose max-w-none" v-html="group.content" />
                </main>
                <BorderDivider class="my-4" />
                <BlogPostsList
                    :blogId="group.id"
                    :blogSlug="group.slug"
                    :class="{ 'mt-6': hasLandingContent }"
                    :pagination="pagination"
                    :posts="posts"
                    :title="postsListTitle"
                    is-group
                />
            </template>

            <!-- Layout with sidebar (hidden on <xl, visible from xl+) -->
            <template v-else>
                <!-- Mobile/tablet layout (<xl): no sidebar -->
                <div class="xl:hidden">
                    <BlogHeader :blog="blogData" :displayedMotto="null" :viewStats="viewStats" />
                    <BorderDivider class="mb-8" />
                    <main v-if="hasLandingContent" class="min-w-0 flex-1">
                        <div class="prose max-w-none" v-html="group.content" />
                    </main>
                    <BlogPostsList
                        :blogId="group.id"
                        :blogSlug="group.slug"
                        :class="{ 'mt-6': hasLandingContent }"
                        :pagination="pagination"
                        :posts="posts"
                        :title="postsListTitle"
                        is-group
                    />
                </div>

                <!-- Desktop layout (xl+): with sidebar -->
                <div class="hidden items-start gap-8 xl:flex">
                    <aside :class="asideOrderClass" :style="asideStyle">
                        <BlogPostsList
                            :blogId="group.id"
                            :blogSlug="group.slug"
                            :pagination="pagination"
                            :posts="posts"
                            :title="postsListTitle"
                            is-group
                        />
                    </aside>
                    <main :class="['min-w-0 flex-1', mainOrderClass]" :style="mainStyle">
                        <BlogHeader :blog="blogData" :displayedMotto="null" :viewStats="viewStats" />
                        <div v-if="hasLandingContent" class="prose max-w-none" v-html="group.content" />
                    </main>
                </div>
            </template>

            <!-- Navigation at bottom -->
            <BlogPostNav :navigation="navigation" />

            <!-- Footer (optional) -->
            <template v-if="hasFooterContent">
                <BorderDivider class="my-4" />
                <BlogFooter :html="group.footer || ''" />
            </template>
        </div>
    </div>
</template>
