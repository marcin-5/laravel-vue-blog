<script lang="ts" setup>
import { useStatsFilters } from '@/composables/useStatsFilters';
import { BLOG_SORT_OPTIONS, POST_SORT_OPTIONS, VISITOR_SORT_OPTIONS } from '@/constants/stats';
import type { BlogOption, BlogRow, FilterState, PostRow, UserOption, VisitorRow } from '@/types/stats';
import { computed } from 'vue';
import StatsFilters from './StatsFilters.vue';
import StatsTable from './StatsTable.vue';

interface Props {
    blogFilters: FilterState;
    postFilters: FilterState;
    visitorFilters?: FilterState;
    blogs: BlogRow[];
    posts: PostRow[];
    visitorsFromPage?: VisitorRow[];
    visitorsFromSpecial?: VisitorRow[];
    bloggers?: UserOption[];
    blogOptions: BlogOption[];
    postBlogOptions?: BlogOption[];
    visitorBlogOptions?: BlogOption[];
    routeName: string;
    showBloggerFilter?: boolean;
    showBloggerColumn?: boolean;
    blogFilterLabel?: string;
}

const props = withDefaults(defineProps<Props>(), {
    showBloggerFilter: false,
    showBloggerColumn: false,
    blogFilterLabel: 'All',
    postBlogOptions: undefined,
    visitorBlogOptions: undefined,
    visitorFilters: undefined,
    visitorsFromPage: () => [],
    visitorsFromSpecial: () => [],
});

const { blogState, postState, visitorState } = useStatsFilters(
    {
        blog: props.blogFilters,
        post: props.postFilters,
        visitor: props.visitorFilters ?? props.blogFilters,
    },
    {
        routeName: props.routeName,
        storageKeyPrefix: props.routeName,
        showBloggerFilter: props.showBloggerFilter,
    },
);

const blogColumns = computed(() => [
    { key: 'name', label: 'Blog' },
    { key: 'owner_name', label: 'Blogger', visible: props.showBloggerColumn },
    { key: 'views', label: 'Blog views' },
    { key: 'post_views', label: 'Post views' },
]);

const postColumns = [
    { key: 'title', label: 'Title' },
    { key: 'views', label: 'Views' },
];

// Columns for Visitor Views (page_views only) — first column reflects group_by only
const pageVisitorColumns = computed(() => [
    {
        key: 'visitor_label',
        label: visitorState.value.group_by === 'fingerprint' ? 'Fingerprint' : 'Visitor',
        hasInfo: true,
    },
    { key: 'blog_views', label: 'Blog views' },
    { key: 'post_views', label: 'Post views' },
    { key: 'lifetime_views', label: 'Lifetime visits' },
]);

// Columns for Anonymous and Bot Views — first column is fixed to User agent
const specialVisitorColumns = [
    { key: 'visitor_label', label: 'User agent', hasInfo: true },
    { key: 'blog_views', label: 'Blog views' },
    { key: 'post_views', label: 'Post views' },
    { key: 'lifetime_views', label: 'Lifetime visits' },
];

const effectivePostBlogOptions = computed(() => (props.postBlogOptions !== undefined ? props.postBlogOptions : props.blogOptions));
const effectiveVisitorBlogOptions = computed(() => (props.visitorBlogOptions !== undefined ? props.visitorBlogOptions : props.blogOptions));

const blogSortOptions = [...BLOG_SORT_OPTIONS];
const postSortOptions = [...POST_SORT_OPTIONS];
const visitorSortOptions = [...VISITOR_SORT_OPTIONS];

// For the first Visitor table (data from page_views) we want the info column
// to reflect current group-by selection: 'visitor' or 'fingerprint'
const visitorInfoKey = computed(() => (visitorState.value.group_by === 'fingerprint' ? 'fingerprint' : 'visitor'));
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
        <!-- Blog Stats Section -->
        <div class="flex flex-col gap-4">
            <h2 class="text-lg font-medium text-sidebar-foreground">Blog Views</h2>
            <StatsFilters
                v-model:selected-blogger="blogState.blogger_id"
                v-model:selected-range="blogState.range"
                v-model:selected-size="blogState.size"
                v-model:selected-sort="blogState.sort"
                :blog-filter-label="blogFilterLabel"
                :blog-options="blogOptions"
                :bloggers="bloggers"
                :show-blog-filter="false"
                :show-blogger-filter="showBloggerFilter"
                :sort-options="blogSortOptions"
            />
            <StatsTable :columns="blogColumns" :data="blogs" row-key="blog_id" />
        </div>

        <div class="h-px w-full bg-sidebar-border/70 dark:bg-sidebar-border"></div>

        <!-- Post Stats Section -->
        <div class="flex flex-col gap-4">
            <h2 class="text-lg font-medium text-sidebar-foreground">Post Views</h2>
            <StatsFilters
                v-model:selected-blog="postState.blog_id"
                v-model:selected-blogger="postState.blogger_id"
                v-model:selected-range="postState.range"
                v-model:selected-size="postState.size"
                v-model:selected-sort="postState.sort"
                :blog-filter-label="blogFilterLabel"
                :blog-options="effectivePostBlogOptions"
                :bloggers="bloggers"
                :show-blogger-filter="showBloggerFilter"
                :sort-options="postSortOptions"
            />
            <StatsTable :columns="postColumns" :data="posts" row-key="post_id" />
        </div>

        <div class="h-px w-full bg-sidebar-border/70 dark:bg-sidebar-border"></div>

        <!-- Visitor Stats Section -->
        <div class="flex flex-col gap-4">
            <h2 class="text-lg font-medium text-sidebar-foreground">Visitor Views</h2>
            <StatsFilters
                v-model:selected-blog="visitorState.blog_id"
                v-model:selected-group-by="visitorState.group_by"
                v-model:selected-range="visitorState.range"
                v-model:selected-size="visitorState.size"
                v-model:selected-sort="visitorState.sort"
                :blog-filter-label="blogFilterLabel"
                :blog-options="effectiveVisitorBlogOptions"
                :show-blog-filter="true"
                :show-blogger-filter="false"
                :show-group-by-filter="true"
                :sort-options="visitorSortOptions"
            />
            <StatsTable :columns="pageVisitorColumns" :data="visitorsFromPage" :info-key="visitorInfoKey" row-key="visitor_label" />
        </div>

        <div class="flex flex-col gap-4">
            <h2 class="text-lg font-medium text-sidebar-foreground">Anonymous and Bot Views</h2>
            <StatsFilters
                v-model:selected-blog="visitorState.blog_id"
                v-model:selected-group-by="visitorState.group_by"
                v-model:selected-range="visitorState.range"
                v-model:selected-size="visitorState.size"
                v-model:selected-sort="visitorState.sort"
                v-model:selected-visitor-type="visitorState.visitor_type"
                :blog-filter-label="blogFilterLabel"
                :blog-options="effectiveVisitorBlogOptions"
                :show-blog-filter="true"
                :show-blogger-filter="false"
                :show-visitor-type-filter="true"
                :sort-options="visitorSortOptions"
            />
            <StatsTable :columns="specialVisitorColumns" :data="visitorsFromSpecial" info-key="user_agent" row-key="visitor_label" />
        </div>
    </div>
</template>
