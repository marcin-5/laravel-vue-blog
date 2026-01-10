import { router } from '@inertiajs/vue3';

/**
 * Gets a cookie value by name.
 */
function getCookie(name: string): string | null {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop()?.split(';').shift() || null;
    return null;
}

/**
 * Initializes and maintains the visitor_id in localStorage and ensures it's sent to the server.
 */
export function initializeVisitorId() {
    if (typeof window === 'undefined') return;

    const LOCAL_STORAGE_KEY = 'visitor_id';

    const syncVisitorId = () => {
        let visitorId = localStorage.getItem(LOCAL_STORAGE_KEY);
        const cookieVisitorId = getCookie('visitor_id');

        if (!visitorId && cookieVisitorId) {
            // First time: sync from cookie to localStorage
            visitorId = cookieVisitorId;
            localStorage.setItem(LOCAL_STORAGE_KEY, visitorId);
        } else if (visitorId && !cookieVisitorId) {
            // Cokie expired or cleared, but we have it in localStorage
            // We'll let the next request carry it via header
        }
    };

    // Initial sync
    syncVisitorId();

    // Attach to Inertia requests
    router.on('before', (event) => {
        const visitorId = localStorage.getItem(LOCAL_STORAGE_KEY);
        if (visitorId) {
            event.detail.visit.headers = {
                ...event.detail.visit.headers,
                'X-Visitor-Id': visitorId,
            };
        }
    });

    // After success, sync again in case server rotated the cookie or it's a new session
    router.on('success', () => {
        syncVisitorId();
    });
}
