import { router } from '@inertiajs/vue3';

export function handleContentClick(event: MouseEvent): void {
    const target = event.target as HTMLElement | null;
    const link = target?.closest('a') as HTMLAnchorElement | null;

    if (link && link.href) {
        // Ignore clicks with modifier keys (e.g., open in new tab)
        if (event.ctrlKey || event.metaKey || event.altKey || event.shiftKey) {
            return;
        }

        // Ignore links explicitly targeting a new window/tab
        if (link.getAttribute('target') === '_blank') {
            return;
        }

        const url = new URL(link.href, window.location.origin);

        // Only intercept same-origin links so Inertia can handle navigation
        if (url.origin === window.location.origin) {
            event.preventDefault();
            router.visit(url.pathname + url.search + url.hash);
        }
    }
}
