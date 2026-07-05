import { router } from '@inertiajs/vue3';

function findClickedLink(event: MouseEvent): HTMLAnchorElement | null {
    const target = event.target as HTMLElement | null;

    return target?.closest('a') as HTMLAnchorElement | null;
}

function hasModifierKey(event: MouseEvent): boolean {
    return event.ctrlKey || event.metaKey || event.altKey || event.shiftKey;
}

function opensInNewBrowsingContext(link: HTMLAnchorElement): boolean {
    return link.getAttribute('target') === '_blank';
}

function isSamePageNavigation(url: URL): boolean {
    return url.pathname === window.location.pathname && url.search === window.location.search;
}

function shouldInterceptLinkClick(event: MouseEvent, link: HTMLAnchorElement, url: URL): boolean {
    const isModifiedClick = hasModifierKey(event);
    const isBlankTarget = opensInNewBrowsingContext(link);
    const isSameOrigin = url.origin === window.location.origin;
    const isSamePage = isSamePageNavigation(url);

    return !isModifiedClick && !isBlankTarget && isSameOrigin && !isSamePage;
}

export function handleContentClick(event: MouseEvent): void {
    const link = findClickedLink(event);

    if (!link?.href) {
        return;
    }

    const url = new URL(link.href, window.location.origin);

    if (!shouldInterceptLinkClick(event, link, url)) {
        return;
    }

    event.preventDefault();
    router.visit(`${url.pathname}${url.search}${url.hash}`);
}
