import type { StatsPageProps } from '@/types/stats';
import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent, h } from 'vue';

const { routerGet } = vi.hoisted(() => ({
    routerGet: vi.fn(),
}));

const { localStorageMock } = vi.hoisted(() => ({
    localStorageMock: {
        clear: vi.fn(),
        getItem: vi.fn(() => null),
        setItem: vi.fn(),
    },
}));

vi.mock('@inertiajs/vue3', () => ({
    router: {
        get: routerGet,
    },
}));

vi.mock('vue-i18n', () => ({
    useI18n: () => ({
        t: (key: string) => key,
    }),
}));

import { useStatsPage } from '../useStatsPage';

const mountedWrappers: { unmount: () => void }[] = [];

function mountStatsPage(props: StatsPageProps): ReturnType<typeof useStatsPage> {
    let result!: ReturnType<typeof useStatsPage>;
    const wrapper = mount(
        defineComponent({
            setup() {
                result = useStatsPage(props);

                return () => h('div');
            },
        }),
    );
    mountedWrappers.push(wrapper);

    return result;
}

const createFilterState = () => ({
    range: 'month' as const,
    sort: 'views_desc',
    size: 10,
});

const createPageProps = (showBloggerFilter: boolean): StatsPageProps => ({
    config: {
        routeName: showBloggerFilter ? 'admin.stats.index' : 'blogger.stats.index',
        showBloggerFilter,
    },
    filters: {
        blog: createFilterState(),
        post: createFilterState(),
        visitor: undefined,
        specialVisitor: undefined,
    },
    data: {
        blogs: [],
        posts: [],
        visitorsFromPage: [],
        visitorsFromSpecial: [],
    },
    options: {
        bloggers: showBloggerFilter ? [{ id: 1, name: 'Admin blogger' }] : undefined,
        blogOptions: [{ id: 1, name: 'Default blog' }],
        postBlogOptions: undefined,
        visitorBlogOptions: [{ id: 2, name: 'Visitor blog' }],
    },
});

describe('useStatsPage', () => {
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

    it('normalizes section options and creates typed filter configurations', () => {
        const { postBlogOptions, visitorBlogOptions, blogFilterConfig, visitorFilterConfig, specialVisitorFilterConfig } = mountStatsPage(
            createPageProps(true),
        );

        expect(postBlogOptions.value).toEqual([{ id: 1, name: 'Default blog' }]);
        expect(visitorBlogOptions.value).toEqual([{ id: 2, name: 'Visitor blog' }]);
        expect(blogFilterConfig.value).toMatchObject({
            showBloggerFilter: true,
            showBlogFilter: false,
            sortOptions: expect.arrayContaining([expect.objectContaining({ value: 'views_desc' })]),
        });
        expect(visitorFilterConfig.value).toMatchObject({ showBlogFilter: true, showGroupByFilter: true });
        expect(specialVisitorFilterConfig.value).toMatchObject({
            showBlogFilter: true,
            showRangeFilter: false,
            showVisitorTypeFilter: true,
        });
    });

    it('keeps blogger and admin blog labels distinct', () => {
        const adminPage = mountStatsPage(createPageProps(true));
        const bloggerPage = mountStatsPage(createPageProps(false));

        expect(adminPage.blogFilterLabel.value).toBe('stats.filters.all_blogs');
        expect(bloggerPage.blogFilterLabel.value).toBe('stats.filters.all_my_blogs');
    });
});
