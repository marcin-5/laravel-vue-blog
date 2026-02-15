import type { FilterState } from '@/types/stats';
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
}

function getInitialState(key: string, serverState: FilterState): FilterState {
    try {
        const saved = localStorage.getItem(key);
        if (saved) {
            const parsed = JSON.parse(saved);
            return {
                range: parsed.range ?? serverState.range,
                sort: parsed.sort ?? serverState.sort,
                size: parsed.size != null ? Number(parsed.size) : serverState.size,
                blogger_id: parsed.blogger_id != null ? Number(parsed.blogger_id) || null : serverState.blogger_id,
                blog_id: parsed.blog_id != null ? Number(parsed.blog_id) || null : serverState.blog_id,
                group_by: parsed.group_by ?? serverState.group_by,
                visitor_type: parsed.visitor_type ?? serverState.visitor_type,
            };
        }
    } catch (e) {
        console.error(`Failed to load filters for ${key}`, e);
    }
    return { ...serverState };
}

function saveState(key: string, state: FilterState): void {
    try {
        localStorage.setItem(key, JSON.stringify(state));
    } catch (e) {
        console.error(e);
    }
}

export function useStatsFilters(serverFilters: { blog: FilterState; post: FilterState; visitor: FilterState }, options: UseStatsFiltersOptions) {
    const blogStorageKey = `stats_blog_filters_${options.storageKeyPrefix}`;
    const postStorageKey = `stats_post_filters_${options.storageKeyPrefix}`;
    const visitorStorageKey = `stats_visitor_filters_${options.storageKeyPrefix}`;

    const blogState = ref<FilterState>(getInitialState(blogStorageKey, serverFilters.blog));
    const postState = ref<FilterState>(getInitialState(postStorageKey, serverFilters.post));
    const visitorState = ref<FilterState>(getInitialState(visitorStorageKey, serverFilters.visitor));

    function applyFilters(): void {
        const query: Record<string, any> = {
            // Blog params
            range: blogState.value.range,
            sort: blogState.value.sort,
            size: blogState.value.size === 0 ? undefined : blogState.value.size,
            blogger_id: options.showBloggerFilter && blogState.value.blogger_id ? blogState.value.blogger_id : undefined,
            // blog_id only when a specific blog is selected
            ...(blogState.value.blog_id != null ? { blog_id: blogState.value.blog_id } : {}),

            // Post params (prefixed)
            posts_range: postState.value.range,
            posts_sort: postState.value.sort,
            posts_size: postState.value.size === 0 ? undefined : postState.value.size,
            posts_blogger_id: options.showBloggerFilter && postState.value.blogger_id ? postState.value.blogger_id : undefined,
            // posts_blog_id only when a specific blog is selected
            ...(postState.value.blog_id != null ? { posts_blog_id: postState.value.blog_id } : {}),

            // Visitor params (prefixed)
            visitors_range: visitorState.value.range,
            visitors_sort: visitorState.value.sort,
            visitors_size: visitorState.value.size === 0 ? undefined : visitorState.value.size,
            visitors_group_by: visitorState.value.group_by,
            visitors_type: visitorState.value.visitor_type,
            // visitors_blog_id only when a specific blog is selected
            ...(visitorState.value.blog_id != null ? { visitors_blog_id: visitorState.value.blog_id } : {}),
        };

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
        [blogState, postState, visitorState],
        () => {
            saveState(blogStorageKey, blogState.value);
            saveState(postStorageKey, postState.value);
            saveState(visitorStorageKey, visitorState.value);
            applyFilters();
        },
        { deep: true },
    );

    onMounted(() => {
        // Check if we need to apply initial state (from localStorage) that differs from server props
        const blogChanged = JSON.stringify(blogState.value) !== JSON.stringify(serverFilters.blog);
        const postChanged = JSON.stringify(postState.value) !== JSON.stringify(serverFilters.post);
        const visitorChanged = JSON.stringify(visitorState.value) !== JSON.stringify(serverFilters.visitor);

        if (blogChanged || postChanged || visitorChanged) {
            applyFilters();
        }
    });

    return {
        blogState,
        postState,
        visitorState,
        applyFilters,
    };
}
