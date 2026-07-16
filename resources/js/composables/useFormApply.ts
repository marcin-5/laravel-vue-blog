import type { InertiaForm } from '@inertiajs/vue3';

/**
 * Returns a handler that patches the given route with the form data,
 * preserving scroll and state — the standard "Apply" action pattern.
 */
export function createApplyHandler<T extends Record<string, any>>(form: InertiaForm<T>, routeName: string, id: number): () => void {
    return () => {
        form.patch(route(routeName, id), {
            preserveScroll: true,
            preserveState: true,
        });
    };
}
