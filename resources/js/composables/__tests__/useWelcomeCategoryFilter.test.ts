import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const { routerGet, routeMock } = vi.hoisted(() => ({
    routerGet: vi.fn(),
    routeMock: vi.fn((name: string) => `/${name}`),
}));

vi.mock('@inertiajs/vue3', () => ({
    router: {
        get: routerGet,
    },
}));

import { toggleCategorySelection, useWelcomeCategoryFilter } from '../useWelcomeCategoryFilter';

describe('useWelcomeCategoryFilter', () => {
    beforeEach(() => {
        routerGet.mockClear();
        routeMock.mockClear();
        vi.stubGlobal('route', routeMock);
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('toggles a category without mutating the selected ids', () => {
        const selectedIds = [2, 4];

        expect(toggleCategorySelection(selectedIds, 7)).toEqual([2, 4, 7]);
        expect(toggleCategorySelection(selectedIds, 2)).toEqual([4]);
        expect(selectedIds).toEqual([2, 4]);
    });

    it('deduplicates selected ids when adding a category', () => {
        expect(toggleCategorySelection([2, 2, 4], 7)).toEqual([2, 4, 7]);
    });

    it('serializes a selected category and applies the shared visit options', () => {
        const { toggleCategory } = useWelcomeCategoryFilter();

        toggleCategory([2, 4], 7);

        expect(routeMock).toHaveBeenCalledWith('home');
        expect(routerGet).toHaveBeenCalledWith(
            '/home',
            { categories: '2,4,7' },
            {
                preserveScroll: true,
                preserveState: true,
                replace: true,
            },
        );
    });

    it('omits the categories query when the last category is removed', () => {
        const { toggleCategory } = useWelcomeCategoryFilter();

        toggleCategory([7], 7);

        expect(routerGet).toHaveBeenCalledWith(
            '/home',
            {},
            {
                preserveScroll: true,
                preserveState: true,
                replace: true,
            },
        );
    });

    it('clears the category query through the home route', () => {
        const { clearFilter } = useWelcomeCategoryFilter();

        clearFilter();

        expect(routerGet).toHaveBeenCalledWith(
            '/home',
            {},
            {
                preserveScroll: true,
                preserveState: true,
                replace: true,
            },
        );
    });
});
