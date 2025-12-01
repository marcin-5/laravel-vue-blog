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

interface Filters {
    range: Range;
    sort: string;
    size: number;
    blogger_id?: number | null;
    blog_id?: number | null;
}

interface Props {
    filters: Filters;
    blogs: BlogRow[];
    posts: PostRow[];
    bloggers?: UserOption[];
    blogOptions: BlogOption[];
    routeName: string;
    showBloggerFilter?: boolean;
    showBloggerColumn?: boolean;
    blogFilterLabel?: string;
}

const props = withDefaults(defineProps<Props>(), {
    showBloggerFilter: false,
    showBloggerColumn: false,
    blogFilterLabel: 'All',
});

// Storage key for localStorage
const STORAGE_KEY = `stats_filters_${props.routeName}`;

// Load saved filters from localStorage or use server defaults
function loadSavedFilters(): Filters {
    try {
        const saved = localStorage.getItem(STORAGE_KEY);
        if (saved) {
            const parsed = JSON.parse(saved);
            return {
                range: parsed.range ?? props.filters.range ?? 'week',
                sort: parsed.sort ?? props.filters.sort ?? 'views_desc',
                size: parsed.size ?? props.filters.size ?? 5,
                blogger_id: parsed.blogger_id ?? props.filters.blogger_id ?? null,
                blog_id: parsed.blog_id ?? props.filters.blog_id ?? null,
            };
        }
    } catch (e) {
        console.error('Failed to load saved filters:', e);
    }
    // No saved data - use defaults with size explicitly set to 5
    return {
        range: props.filters.range ?? 'week',
        sort: props.filters.sort ?? 'views_desc',
        size: 5, // Always default to 5 on first visit
        blogger_id: props.filters.blogger_id ?? null,
        blog_id: props.filters.blog_id ?? null,
    };
}

// Save filters to localStorage
function saveFilters() {
    try {
        const toSave = {
            range: selectedRange.value,
            sort: selectedSort.value,
            size: selectedSize.value,
            blogger_id: selectedBlogger.value,
            blog_id: selectedBlog.value,
        };
        localStorage.setItem(STORAGE_KEY, JSON.stringify(toSave));
    } catch (e) {
        console.error('Failed to save filters:', e);
    }
}

const savedFilters = loadSavedFilters();

const selectedRange = ref<Range>(savedFilters.range);
const selectedSize = ref<number>(savedFilters.size);
const selectedSort = ref<string>(savedFilters.sort);
const selectedBlogger = ref<number | null | undefined>(savedFilters.blogger_id);
const selectedBlog = ref<number | null | undefined>(savedFilters.blog_id);

// When blogger changes, reset blog filter to avoid stale selection
watch(selectedBlogger, () => {
    selectedBlog.value = null;
});

// Auto-refresh on filter changes
watch([selectedRange, selectedSize, selectedSort, selectedBlogger, selectedBlog], () => {
    saveFilters();
    applyFilters();
});

// Load initial data if saved filters differ from server defaults
onMounted(() => {
    if (
        savedFilters.range !== props.filters.range ||
        savedFilters.sort !== props.filters.sort ||
        savedFilters.size !== props.filters.size ||
        savedFilters.blogger_id !== props.filters.blogger_id ||
        savedFilters.blog_id !== props.filters.blog_id
    ) {
        applyFilters();
    }
});

const blogColumns = computed(() => [
    { key: 'name', label: 'Blog' },
    { key: 'owner_name', label: 'Blogger', visible: props.showBloggerColumn },
    { key: 'views', label: 'Views' },
]);

const postColumns = [
    { key: 'title', label: 'Title' },
    { key: 'views', label: 'Views' },
];

const query = computed(() => {
    const q: Record<string, unknown> = {
        range: selectedRange.value,
        sort: selectedSort.value,
        size: selectedSize.value === 0 ? undefined : selectedSize.value,
    };
    if (props.showBloggerFilter && selectedBlogger.value) q.blogger_id = selectedBlogger.value;
    if (selectedBlog.value) q.blog_id = selectedBlog.value;
    return q;
});

function applyFilters() {
    router.get(route(props.routeName), query.value as any, { preserveScroll: true, preserveState: true });
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
        <StatsFilters
            v-model:selected-blog="selectedBlog"
            v-model:selected-blogger="selectedBlogger"
            v-model:selected-range="selectedRange"
            v-model:selected-size="selectedSize"
            v-model:selected-sort="selectedSort"
            :blog-filter-label="blogFilterLabel"
            :blog-options="blogOptions"
            :bloggers="bloggers"
            :show-blogger-filter="showBloggerFilter"
        />

        <StatsTable :columns="blogColumns" :data="blogs" row-key="blog_id" title="Blog views" />

        <StatsTable v-if="filters.blog_id" :columns="postColumns" :data="posts" row-key="post_id" title="Post views" />
    </div>
</template>
