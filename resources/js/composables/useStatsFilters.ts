import type { FilterState, StatsGroupBy, StatsQuery, StatsRange, StatsVisitorType } from '@/types/stats';
import { router } from '@inertiajs/vue3';
import { onMounted, ref, watch } from 'vue';

export interface UseStatsFiltersOptions {
    routeName: string;
    storageKeyPrefix: string;
    showBloggerFilter?: boolean;
}

export interface StatsFiltersState {
    blog: FilterState;
    post: FilterState;
    visitor: FilterState;
    specialVisitor: FilterState;
}

export interface StatsFilterStorageKeys {
    blog: string;
    post: string;
    visitor: string;
    specialVisitor: string;
}

function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null;
}

function isStatsRange(value: unknown): value is StatsRange {
    return ['today', 'week', 'month', 'half_year', 'year', 'lifetime'].includes(value as string);
}

function isStatsGroupBy(value: unknown): value is StatsGroupBy {
    return value === 'visitor_id' || value === 'fingerprint';
}

function isStatsVisitorType(value: unknown): value is StatsVisitorType {
    return value === 'all' || value === 'bots' || value === 'anonymous' || value === 'markdown';
}

function normalizeNumber(value: unknown, fallback: number): number {
    if (value == null) {
        return fallback;
    }

    const number = Number(value);

    return Number.isFinite(number) ? number : fallback;
}

function normalizeNullableNumber(value: unknown, fallback?: number | null): number | null | undefined {
    if (value == null) {
        return fallback;
    }

    const number = Number(value);

    return Number.isFinite(number) ? number || null : fallback;
}

export function normalizeFilterState(value: unknown, serverState: FilterState): FilterState {
    const savedState = isRecord(value) ? value : {};

    return {
        range: isStatsRange(savedState.range) ? savedState.range : serverState.range,
        sort: typeof savedState.sort === 'string' ? savedState.sort : serverState.sort,
        size: normalizeNumber(savedState.size, serverState.size),
        blogger_id: normalizeNullableNumber(savedState.blogger_id, serverState.blogger_id),
        blog_id: normalizeNullableNumber(savedState.blog_id, serverState.blog_id),
        group_by: isStatsGroupBy(savedState.group_by) ? savedState.group_by : serverState.group_by,
        visitor_type: isStatsVisitorType(savedState.visitor_type) ? savedState.visitor_type : serverState.visitor_type,
    };
}

export function getStatsFilterStorageKeys(storageKeyPrefix: string): StatsFilterStorageKeys {
    return {
        blog: `stats_blog_filters_${storageKeyPrefix}`,
        post: `stats_post_filters_${storageKeyPrefix}`,
        visitor: `stats_visitor_filters_${storageKeyPrefix}`,
        specialVisitor: `stats_special_visitor_filters_${storageKeyPrefix}`,
    };
}

export function readStatsFilterState(key: string, serverState: FilterState): FilterState {
    if (typeof window === 'undefined') {
        return { ...serverState };
    }

    try {
        const saved = localStorage.getItem(key);
        if (saved) {
            return normalizeFilterState(JSON.parse(saved) as unknown, serverState);
        }
    } catch (error) {
        console.error(`Failed to load filters for ${key}`, error);
    }
    return { ...serverState };
}

export function saveStatsFilterState(key: string, state: FilterState): void {
    if (typeof window === 'undefined') {
        return;
    }

    try {
        localStorage.setItem(key, JSON.stringify(state));
    } catch (error) {
        console.error(error);
    }
}

export function buildStatsQuery(states: StatsFiltersState, showBloggerFilter = false): StatsQuery {
    const { blog, post, visitor, specialVisitor } = states;

    return {
        range: blog.range,
        sort: blog.sort,
        size: blog.size,
        blogger_id: showBloggerFilter && blog.blogger_id ? blog.blogger_id : undefined,
        ...(blog.blog_id != null ? { blog_id: blog.blog_id } : {}),
        posts_range: post.range,
        posts_sort: post.sort,
        posts_size: post.size,
        posts_blogger_id: showBloggerFilter && post.blogger_id ? post.blogger_id : undefined,
        ...(post.blog_id != null ? { posts_blog_id: post.blog_id } : {}),
        visitors_range: visitor.range,
        visitors_sort: visitor.sort,
        visitors_size: visitor.size,
        visitors_group_by: visitor.group_by,
        visitors_type: visitor.visitor_type,
        ...(visitor.blog_id != null ? { visitors_blog_id: visitor.blog_id } : {}),
        special_visitors_range: specialVisitor.range,
        special_visitors_sort: specialVisitor.sort,
        special_visitors_size: specialVisitor.size,
        special_visitors_group_by: specialVisitor.group_by,
        special_visitors_type: specialVisitor.visitor_type,
        ...(specialVisitor.blog_id != null ? { special_visitors_blog_id: specialVisitor.blog_id } : {}),
    };
}

export function useStatsFilters(
    serverFilters: { blog: FilterState; post: FilterState; visitor: FilterState; specialVisitor: FilterState },
    options: UseStatsFiltersOptions,
) {
    const storageKeys = getStatsFilterStorageKeys(options.storageKeyPrefix);

    const blogState = ref<FilterState>(readStatsFilterState(storageKeys.blog, serverFilters.blog));
    const postState = ref<FilterState>(readStatsFilterState(storageKeys.post, serverFilters.post));
    const visitorState = ref<FilterState>(readStatsFilterState(storageKeys.visitor, serverFilters.visitor));
    const specialVisitorState = ref<FilterState>(readStatsFilterState(storageKeys.specialVisitor, serverFilters.specialVisitor));

    function applyFilters(): void {
        const query = buildStatsQuery(
            {
                blog: blogState.value,
                post: postState.value,
                visitor: visitorState.value,
                specialVisitor: specialVisitorState.value,
            },
            options.showBloggerFilter,
        );

        router.get(route(options.routeName), query, { preserveScroll: true, preserveState: true });
    }

    // Watchers to reset child filters when blogger changes
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
    watch(
        [blogState, postState, visitorState, specialVisitorState],
        () => {
            saveStatsFilterState(storageKeys.blog, blogState.value);
            saveStatsFilterState(storageKeys.post, postState.value);
            saveStatsFilterState(storageKeys.visitor, visitorState.value);
            saveStatsFilterState(storageKeys.specialVisitor, specialVisitorState.value);
            applyFilters();
        },
        { deep: true },
    );

    onMounted(() => {
        // Check if we need to apply initial state (from localStorage) that differs from server props
        const blogChanged = JSON.stringify(blogState.value) !== JSON.stringify(serverFilters.blog);
        const postChanged = JSON.stringify(postState.value) !== JSON.stringify(serverFilters.post);
        const visitorChanged = JSON.stringify(visitorState.value) !== JSON.stringify(serverFilters.visitor);
        const specialVisitorChanged = JSON.stringify(specialVisitorState.value) !== JSON.stringify(serverFilters.specialVisitor);

        if (blogChanged || postChanged || visitorChanged || specialVisitorChanged) {
            applyFilters();
        }
    });

    return {
        blogState,
        postState,
        visitorState,
        specialVisitorState,
        applyFilters,
    };
}
