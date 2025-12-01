<script lang="ts" setup>
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
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

const selectedRange = ref<Range>(props.filters.range ?? 'week');
const selectedSize = ref<number>(props.filters.size ?? 5);
const selectedSort = ref<string>(props.filters.sort ?? 'views_desc');
const selectedBlogger = ref<number | null | undefined>(props.filters.blogger_id ?? null);
const selectedBlog = ref<number | null | undefined>(props.filters.blog_id ?? null);

// When blogger changes, reset blog filter to avoid stale selection
watch(selectedBlogger, () => {
    selectedBlog.value = null;
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
            @apply="applyFilters"
        />

        <StatsTable :columns="blogColumns" :data="blogs" row-key="blog_id" title="Blog views" />

        <StatsTable v-if="filters.blog_id" :columns="postColumns" :data="posts" row-key="post_id" title="Post views" />
    </div>
</template>
