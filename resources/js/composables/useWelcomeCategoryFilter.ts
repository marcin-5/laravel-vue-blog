import { router } from '@inertiajs/vue3';

export interface WelcomeCategoryFilterActions {
    toggleCategory: (selectedIds: readonly number[] | undefined, categoryId: number) => void;
    clearFilter: () => void;
}

export type WelcomeCategoryQuery = Record<string, string>;

export function toggleCategorySelection(selectedIds: readonly number[] | undefined, categoryId: number): number[] {
    const nextIds = new Set(selectedIds ?? []);

    if (nextIds.has(categoryId)) {
        nextIds.delete(categoryId);
    } else {
        nextIds.add(categoryId);
    }

    return Array.from(nextIds);
}

function navigate(query: WelcomeCategoryQuery): void {
    router.get(route('home'), query, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
}

export function useWelcomeCategoryFilter(): WelcomeCategoryFilterActions {
    function toggleCategory(selectedIds: readonly number[] | undefined, categoryId: number): void {
        const nextIds = toggleCategorySelection(selectedIds, categoryId);
        const query: WelcomeCategoryQuery = nextIds.length > 0 ? { categories: nextIds.join(',') } : {};

        navigate(query);
    }

    function clearFilter(): void {
        navigate({});
    }

    return {
        toggleCategory,
        clearFilter,
    };
}
