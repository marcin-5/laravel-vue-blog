import type { BlogItem, CategoryItem } from '@/types/blog.types';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import Welcome from '../Welcome.vue';

const { clearFilter, navigateCategory } = vi.hoisted(() => ({
    clearFilter: vi.fn(),
    navigateCategory: vi.fn(),
}));

vi.mock('@/components/AppLogo.vue', () => ({
    default: { name: 'AppLogo', template: '<div class="app-logo" />' },
}));

vi.mock('@/components/blog/CategoriesFilter.vue', () => ({
    default: {
        name: 'CategoriesFilter',
        props: ['categories', 'clearLabel', 'selectedIds'],
        emits: ['clear', 'toggle'],
        template: '<div class="categories-filter" />',
    },
}));

vi.mock('@/components/blog/BlogsGrid.vue', () => ({
    default: {
        name: 'BlogsGrid',
        props: ['blogs'],
        template: '<div class="blogs-grid" />',
    },
}));

vi.mock('@/components/blog/NoBlogs.vue', () => ({
    default: { name: 'NoBlogs', template: '<div class="no-blogs" />' },
}));

vi.mock('@/components/blog/UserGroupsList.vue', () => ({
    default: { name: 'UserGroupsList', template: '<div class="user-groups-list" />' },
}));

vi.mock('@/layouts/PublicHomeLayout.vue', () => ({
    default: { name: 'PublicHomeLayout', template: '<main><slot /></main>' },
}));

vi.mock('@/composables/useWelcomeCategoryFilter', () => ({
    useWelcomeCategoryFilter: () => ({
        clearFilter,
        toggleCategory: navigateCategory,
    }),
}));

vi.mock('vue-i18n', () => ({
    useI18n: () => ({
        t: (key: string, fallback?: string) => fallback ?? key,
    }),
}));

describe('Welcome.vue', () => {
    const categories: CategoryItem[] = [
        { id: 2, name: 'Vue' },
        { id: 4, name: 'Laravel' },
    ];
    const blogs: BlogItem[] = [
        {
            id: 1,
            name: 'Blog',
            slug: 'blog',
            url: 'https://blog.test',
            author: 'Author',
            categories,
        },
    ];

    beforeEach(() => {
        clearFilter.mockReset();
        navigateCategory.mockReset();
    });

    it('forwards page data and renders the non-empty blog state', () => {
        const wrapper = mount(Welcome, {
            props: { blogs, categories, selectedCategoryIds: [2], displayedSlogan: 'A stable slogan' },
        });
        const filter = wrapper.findComponent({ name: 'CategoriesFilter' });

        expect(wrapper.text()).toContain('A stable slogan');
        expect(filter.props()).toMatchObject({
            categories,
            clearLabel: 'Clear filter',
            selectedIds: [2],
        });
        expect(wrapper.findComponent({ name: 'BlogsGrid' }).props('blogs')).toEqual(blogs);
        expect(wrapper.findComponent({ name: 'NoBlogs' }).exists()).toBe(false);
    });

    it('does not render a slogan when it is not provided', () => {
        const wrapper = mount(Welcome, {
            props: { blogs, categories },
        });

        expect(wrapper.text()).not.toContain('—');
    });

    it('uses the empty state when no blogs are provided', () => {
        const wrapper = mount(Welcome, {
            props: { blogs: [], categories },
        });

        expect(wrapper.findComponent({ name: 'BlogsGrid' }).exists()).toBe(false);
        expect(wrapper.findComponent({ name: 'NoBlogs' }).exists()).toBe(true);
        expect(wrapper.findComponent({ name: 'CategoriesFilter' }).props('selectedIds')).toEqual([]);
    });

    it('wires filter events to the composable actions', async () => {
        const wrapper = mount(Welcome, {
            props: { blogs, categories, selectedCategoryIds: [2] },
        });
        const filter = wrapper.findComponent({ name: 'CategoriesFilter' });

        filter.vm.$emit('toggle', 4);
        filter.vm.$emit('clear');
        await wrapper.vm.$nextTick();

        expect(navigateCategory).toHaveBeenCalledWith([2], 4);
        expect(clearFilter).toHaveBeenCalledOnce();
    });
});
