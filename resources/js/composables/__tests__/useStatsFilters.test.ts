import type { FilterState } from '@/types/stats';
import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent, h, nextTick } from 'vue';

const { routerGet } = vi.hoisted(() => ({
    routerGet: vi.fn(),
}));

const { localStorageMock } = vi.hoisted(() => {
    const values = new Map<string, string>();

    return {
        localStorageMock: {
            clear: () => values.clear(),
            getItem: (key: string) => values.get(key) ?? null,
            setItem: (key: string, value: string) => values.set(key, value),
        },
    };
});

vi.mock('@inertiajs/vue3', () => ({
    router: {
        get: routerGet,
    },
}));

import { buildStatsQuery, getStatsFilterStorageKeys, normalizeFilterState, readStatsFilterState, useStatsFilters } from '../useStatsFilters';

const mountedWrappers: { unmount: () => void }[] = [];

function mountStatsFilters(...args: Parameters<typeof useStatsFilters>): ReturnType<typeof useStatsFilters> {
    let result!: ReturnType<typeof useStatsFilters>;
    const wrapper = mount(
        defineComponent({
            setup() {
                result = useStatsFilters(...args);

                return () => h('div');
            },
        }),
    );
    mountedWrappers.push(wrapper);

    return result;
}

const createFilterState = (overrides: Partial<FilterState> = {}): FilterState => ({
    range: 'month',
    sort: 'views_desc',
    size: 10,
    ...overrides,
});

const createServerFilters = () => ({
    blog: createFilterState(),
    post: createFilterState({ sort: 'title_desc' }),
    visitor: createFilterState({ group_by: 'visitor_id', visitor_type: 'all' }),
    specialVisitor: createFilterState({ range: 'lifetime', visitor_type: 'bots' }),
});

describe('useStatsFilters', () => {
    afterEach(() => {
        mountedWrappers.splice(0).forEach((wrapper) => wrapper.unmount());
    });
    beforeEach(() => {
        vi.stubGlobal('localStorage', localStorageMock);
        localStorageMock.clear();
        routerGet.mockClear();
        vi.stubGlobal(
            'route',
            vi.fn((name: string) => `/route/${name}`),
        );
    });

    it('normalizes unknown persisted values using the server state', () => {
        const fallback = createFilterState({ blogger_id: 7, blog_id: 3, group_by: 'fingerprint', visitor_type: 'markdown' });

        expect(
            normalizeFilterState(
                {
                    range: 'invalid',
                    sort: 42,
                    size: 'invalid',
                    blogger_id: '9',
                    blog_id: 0,
                    group_by: 'invalid',
                    visitor_type: 'invalid',
                },
                fallback,
            ),
        ).toEqual({
            ...fallback,
            blogger_id: 9,
            blog_id: null,
        });
    });

    it('reads persisted state without accessing it during SSR', () => {
        const fallback = createFilterState();
        localStorage.setItem('stats_blog_filters_test', JSON.stringify({ size: '20', blog_id: '4' }));

        expect(readStatsFilterState('stats_blog_filters_test', fallback)).toEqual({ ...fallback, size: 20, blog_id: 4 });

        vi.stubGlobal('window', undefined);
        expect(readStatsFilterState('stats_blog_filters_test', fallback)).toEqual(fallback);
        vi.unstubAllGlobals();
        vi.stubGlobal('localStorage', localStorageMock);
        vi.stubGlobal(
            'route',
            vi.fn((name: string) => `/route/${name}`),
        );
    });

    it('builds the existing query prefixes and omits unselected blog ids', () => {
        const states = createServerFilters();
        states.blog.blogger_id = 11;
        states.blog.blog_id = 4;
        states.post.blogger_id = 11;
        states.visitor.blog_id = 5;

        const query = buildStatsQuery(states, true);

        expect(query).toMatchObject({
            range: 'month',
            sort: 'views_desc',
            size: 10,
            blogger_id: 11,
            blog_id: 4,
            posts_range: 'month',
            posts_sort: 'title_desc',
            posts_size: 10,
            posts_blogger_id: 11,
            visitors_range: 'month',
            visitors_group_by: 'visitor_id',
            visitors_type: 'all',
            visitors_blog_id: 5,
            special_visitors_range: 'lifetime',
            special_visitors_type: 'bots',
        });
        expect(query).not.toHaveProperty('posts_blog_id');
        expect(query).not.toHaveProperty('special_visitors_blog_id');

        expect(buildStatsQuery(states, false)).toMatchObject({
            blogger_id: undefined,
            posts_blogger_id: undefined,
        });
    });

    it('uses stable persistence keys and applies changed filters through Inertia', async () => {
        expect(getStatsFilterStorageKeys('blogger.stats.index')).toEqual({
            blog: 'stats_blog_filters_blogger.stats.index',
            post: 'stats_post_filters_blogger.stats.index',
            visitor: 'stats_visitor_filters_blogger.stats.index',
            specialVisitor: 'stats_special_visitor_filters_blogger.stats.index',
        });

        const filters = mountStatsFilters(createServerFilters(), {
            routeName: 'blogger.stats.index',
            storageKeyPrefix: 'blogger.stats.index',
            showBloggerFilter: true,
        });

        filters.blogState.value.blog_id = 12;
        await nextTick();

        expect(localStorage.getItem('stats_blog_filters_blogger.stats.index')).toContain('"blog_id":12');
        expect(routerGet).toHaveBeenCalledWith('/route/blogger.stats.index', expect.objectContaining({ blog_id: 12 }), {
            preserveScroll: true,
            preserveState: true,
        });
    });

    it('resets only the dependent blog when a blogger changes', async () => {
        const filters = mountStatsFilters(createServerFilters(), {
            routeName: 'admin.stats.index',
            storageKeyPrefix: 'admin.stats.index',
            showBloggerFilter: true,
        });

        filters.blogState.value.blog_id = 12;
        filters.postState.value.blog_id = 13;
        filters.blogState.value.blogger_id = 8;
        await nextTick();

        expect(filters.blogState.value.blog_id).toBeNull();
        expect(filters.postState.value.blog_id).toBe(13);
    });
});
