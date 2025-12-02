<script lang="ts" setup>
import { router } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import StatsFilters from './StatsFilters.vue';
import StatsTable from './StatsTable.vue';

type Range = 'week' | 'month' | 'half_year' | 'year';
type BlogRow = { blog_id: number; name: string; owner_id: number; owner_name: string; views: number };
type PostRow = { post_id: number; title: string; views: number };
type UserOption = { id: number; name: string };
type BlogOption = { id: number; name: string };

interface FilterState {
    range: Range;
    sort: string;
    size: number;
    blogger_id?: number | null;
    blog_id?: number | null;
}

interface Props {
    blogFilters: FilterState;
    postFilters: FilterState;
    blogs: BlogRow[];
    posts: PostRow[];
    bloggers?: UserOption[];
    blogOptions: BlogOption[];
    postBlogOptions?: BlogOption[];
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
});

// Storage keys
const BLOG_STORAGE_KEY = `stats_blog_filters_${props.routeName}`;
const POST_STORAGE_KEY = `stats_post_filters_${props.routeName}`;

function getInitialState(key: string, serverState: FilterState): FilterState {
    try {
        const saved = localStorage.getItem(key);
        if (saved) {
            const parsed = JSON.parse(saved);
            return {
                range: parsed.range ?? serverState.range,
                sort: parsed.sort ?? serverState.sort,
                size: parsed.size ?? serverState.size,
                blogger_id: parsed.blogger_id ?? serverState.blogger_id,
                blog_id: parsed.blog_id ?? serverState.blog_id,
            };
        }
    } catch (e) {
        console.error(`Failed to load filters for ${key}`, e);
    }
    return { ...serverState };
}

const blogState = ref<FilterState>(getInitialState(BLOG_STORAGE_KEY, props.blogFilters));
const postState = ref<FilterState>(getInitialState(POST_STORAGE_KEY, props.postFilters));

// Watchers to reset child filters
watch(
    () => blogState.value.blogger_id,
    () => {
        blogState.value.blog_id = null;
    },
);
watch(
    () => postState.value.blogger_id,
    () => {
        postState.value.blog_id = null;
    },
);

// Persistence and Application
function saveState(key: string, state: FilterState) {
    try {
        localStorage.setItem(key, JSON.stringify(state));
    } catch (e) {
        console.error(e);
    }
}

watch(
    [blogState, postState],
    () => {
        saveState(BLOG_STORAGE_KEY, blogState.value);
        saveState(POST_STORAGE_KEY, postState.value);
        applyFilters();
    },
    { deep: true },
);

onMounted(() => {
    // Check if we need to apply initial state (from localStorage) that differs from server props
    const blogChanged = JSON.stringify(blogState.value) !== JSON.stringify(props.blogFilters);
    const postChanged = JSON.stringify(postState.value) !== JSON.stringify(props.postFilters);

    if (blogChanged || postChanged) {
        applyFilters();
    }
});

function applyFilters() {
    const query: Record<string, any> = {
        // Blog params
        range: blogState.value.range,
        sort: blogState.value.sort,
        size: blogState.value.size === 0 ? undefined : blogState.value.size,
        blogger_id: props.showBloggerFilter && blogState.value.blogger_id ? blogState.value.blogger_id : undefined,
        blog_id: blogState.value.blog_id,

        // Post params (prefixed)
        posts_range: postState.value.range,
        posts_sort: postState.value.sort,
        posts_size: postState.value.size === 0 ? undefined : postState.value.size,
        posts_blogger_id: props.showBloggerFilter && postState.value.blogger_id ? postState.value.blogger_id : undefined,
        posts_blog_id: postState.value.blog_id,
    };

    router.get(route(props.routeName), query, { preserveScroll: true, preserveState: true });
}

const blogColumns = computed(() => [
    { key: 'name', label: 'Blog' },
    { key: 'owner_name', label: 'Blogger', visible: props.showBloggerColumn },
    { key: 'views', label: 'Views' },
]);

const postColumns = [
    { key: 'title', label: 'Title' },
    { key: 'views', label: 'Views' },
];

const effectivePostBlogOptions = computed(() => (props.postBlogOptions !== undefined ? props.postBlogOptions : props.blogOptions));

const blogSortOptions = [
    { value: 'views_desc', label: 'Views ↓' },
    { value: 'views_asc', label: 'Views ↑' },
    { value: 'name_asc', label: 'Name A→Z' },
    { value: 'name_desc', label: 'Name Z→A' },
];

const postSortOptions = [
    { value: 'views_desc', label: 'Views ↓' },
    { value: 'views_asc', label: 'Views ↑' },
    { value: 'title_asc', label: 'Title A→Z' },
    { value: 'title_desc', label: 'Title Z→A' },
];
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
    </div>
</template>
