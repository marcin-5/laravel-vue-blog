import StatsPage from '@/components/stats/StatsPage.vue';
import type { FilterState, StatsFilterConfig, StatsPageProps } from '@/types/stats';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { defineComponent, h, ref } from 'vue';

const filterState: FilterState = {
    range: 'month',
    sort: 'views_desc',
    size: 10,
};

const configs = {
    blog: { blogOptions: [], sortOptions: [], showBlogFilter: false },
    post: { blogOptions: [], sortOptions: [], showBloggerFilter: true },
    visitor: { blogOptions: [], sortOptions: [], showBlogFilter: true, showGroupByFilter: true },
    specialVisitor: { blogOptions: [], sortOptions: [], showBlogFilter: true, showRangeFilter: false, showVisitorTypeFilter: true },
} satisfies Record<string, StatsFilterConfig>;

const states = {
    blog: ref(filterState),
    post: ref(filterState),
    visitor: ref(filterState),
    specialVisitor: ref(filterState),
};

vi.mock('@/composables/useStatsPage', () => ({
    useStatsPage: () => ({
        blogState: states.blog,
        postState: states.post,
        visitorState: states.visitor,
        specialVisitorState: states.specialVisitor,
        blogFilterConfig: ref(configs.blog),
        postFilterConfig: ref(configs.post),
        visitorFilterConfig: ref(configs.visitor),
        specialVisitorFilterConfig: ref(configs.specialVisitor),
    }),
}));

vi.mock('vue-i18n', () => ({
    useI18n: () => ({
        t: (key: string) => key,
    }),
}));

const SectionStub = defineComponent({
    props: ['data', 'filterConfig', 'showBloggerColumn'],
    setup(props) {
        return () => h('div', { 'data-test': 'stats-section', 'data-config': props.filterConfig?.showBlogFilter });
    },
});

const pageProps: StatsPageProps = {
    config: {
        routeName: 'admin.stats.index',
        showBloggerFilter: true,
        showBloggerColumn: true,
    },
    filters: {
        blog: filterState,
        post: filterState,
        visitor: filterState,
        specialVisitor: filterState,
    },
    data: {
        blogs: [
            {
                blog_id: 1,
                name: 'Main blog',
                owner_id: 1,
                owner_name: 'Admin',
                views: 1,
                unique_views: 1,
                post_views: 1,
                unique_post_views: 1,
                markdown_views: 1,
            },
        ],
        posts: [],
        visitorsFromPage: [],
        visitorsFromSpecial: [],
    },
    options: {
        blogOptions: [],
    },
};

describe('StatsPage', () => {
    it('passes one filter configuration to each semantic section', () => {
        const wrapper = mount(StatsPage, {
            props: pageProps,
            global: {
                stubs: {
                    BlogStats: SectionStub,
                    PostStats: SectionStub,
                    AudienceStats: SectionStub,
                    SpecialAudienceStats: SectionStub,
                },
            },
        });

        const sections = wrapper.findAll('[data-test="stats-section"]');

        expect(sections).toHaveLength(4);
        expect(sections[0]?.attributes('data-config')).toBe('false');
        expect(sections[1]?.attributes('data-config')).toBeUndefined();
        expect(sections[2]?.attributes('data-config')).toBe('true');
        expect(sections[3]?.attributes('data-config')).toBe('true');
    });
});
