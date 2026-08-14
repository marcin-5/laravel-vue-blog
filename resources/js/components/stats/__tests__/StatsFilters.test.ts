import StatsFilters from '@/components/stats/StatsFilters.vue';
import type { FilterState, StatsFilterConfig } from '@/types/stats';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { defineComponent } from 'vue';

vi.mock('vue-i18n', () => ({
    useI18n: () => ({
        t: (key: string) => key,
    }),
}));

const FilterSelectStub = defineComponent({
    name: 'FilterSelect',
    props: ['label', 'options', 'modelValue'],
    template: '<div data-test="filter-select">{{ label }}</div>',
});

const filterState: FilterState = {
    range: 'month',
    sort: 'views_desc',
    size: 10,
};

const config: StatsFilterConfig = {
    bloggers: [{ id: 1, name: 'Admin blogger' }],
    blogOptions: [{ id: 2, name: 'Main blog' }],
    blogFilterLabel: 'All blogs',
    showBloggerFilter: true,
    showBlogFilter: true,
    showGroupByFilter: true,
    showVisitorTypeFilter: true,
    showRangeFilter: true,
    sortOptions: [{ value: 'views_desc', label: 'Views down' }],
};

describe('StatsFilters', () => {
    it('renders controls and options from the grouped configuration', () => {
        const wrapper = mount(StatsFilters, {
            props: {
                config,
                modelValue: filterState,
            },
            global: {
                stubs: {
                    FilterSelect: FilterSelectStub,
                },
            },
        });

        const filters = wrapper.findAllComponents(FilterSelectStub);

        expect(filters).toHaveLength(7);
        expect(filters[3]?.props('options')).toEqual([{ value: 1, label: 'Admin blogger' }]);
        expect(filters[4]?.props('options')).toEqual([
            { value: 'all', label: 'All blogs' },
            { value: 2, label: 'Main blog' },
        ]);
    });

    it('hides optional controls when disabled in the grouped configuration', () => {
        const wrapper = mount(StatsFilters, {
            props: {
                config: {
                    blogOptions: config.blogOptions,
                    sortOptions: config.sortOptions,
                    showBloggerFilter: false,
                    showBlogFilter: false,
                    showGroupByFilter: false,
                    showVisitorTypeFilter: false,
                    showRangeFilter: false,
                },
                modelValue: filterState,
            },
            global: {
                stubs: {
                    FilterSelect: FilterSelectStub,
                },
            },
        });

        expect(wrapper.findAllComponents(FilterSelectStub)).toHaveLength(2);
    });
});
