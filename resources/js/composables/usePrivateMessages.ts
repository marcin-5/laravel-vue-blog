import type {
    PrivateConversationContext,
    PrivateConversationFilters,
    PrivateConversationSortDirection,
    PrivateConversationSortField,
} from '@/types/private-messaging.types';
import { router } from '@inertiajs/vue3';
import type { AcceptableValue } from 'reka-ui';
import { readonly, shallowRef } from 'vue';

const SORT_FIELDS: PrivateConversationSortField[] = ['subject', 'created_at', 'updated_at'];

export function usePrivateMessages(initialFilters: PrivateConversationFilters) {
    const sortBy = shallowRef<PrivateConversationSortField>(initialFilters.sort_by);
    const sortDir = shallowRef<PrivateConversationSortDirection>(initialFilters.sort_dir);
    const perPage = shallowRef(String(initialFilters.per_page));
    const contextId = shallowRef<number | null>(initialFilters.context_id);
    const context: PrivateConversationContext = initialFilters.context;
    const indexRoute = context === 'blog' ? 'blog-private-conversations.index' : 'group-private-conversations.index';

    function reload(): void {
        router.get(
            route(indexRoute),
            {
                [`${context}_id`]: contextId.value ?? undefined,
                sort_by: sortBy.value,
                sort_dir: sortDir.value,
                per_page: perPage.value,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function asString(value: AcceptableValue | undefined): string | null {
        return value == null ? null : String(value);
    }

    function changeSortBy(value: AcceptableValue | undefined): void {
        const normalized = asString(value);
        if (normalized && SORT_FIELDS.includes(normalized as PrivateConversationSortField)) {
            sortBy.value = normalized as PrivateConversationSortField;
            reload();
        }
    }

    function changeContext(value: AcceptableValue | undefined): void {
        const normalized = asString(value);
        contextId.value = normalized ? Number(normalized) : null;
        reload();
    }

    function changeSortDir(value: AcceptableValue | undefined): void {
        const normalized = asString(value);
        if (normalized === 'asc' || normalized === 'desc') {
            sortDir.value = normalized;
            reload();
        }
    }

    function changePerPage(value: AcceptableValue | undefined): void {
        const normalized = asString(value);
        if (normalized && Number(normalized) > 0) {
            perPage.value = normalized;
            reload();
        }
    }

    function visitPage(url: string | null): void {
        if (url) router.visit(url, { preserveState: true, preserveScroll: true });
    }

    return {
        sortBy: readonly(sortBy),
        sortDir: readonly(sortDir),
        perPage: readonly(perPage),
        contextId: readonly(contextId),
        changeContext,
        changeSortBy,
        changeSortDir,
        changePerPage,
        visitPage,
    };
}
