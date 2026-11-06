import { router } from '@inertiajs/vue3';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { initializeVisitorId } from '../useVisitorId';

vi.mock('@inertiajs/vue3', () => ({
    router: {
        on: vi.fn(),
    },
}));

describe('useVisitorId', () => {
    const LOCAL_STORAGE_KEY = 'visitor_id';

    beforeEach(() => {
        vi.clearAllMocks();
        localStorage.clear();
        document.cookie = '';
    });

    it('syncs visitor_id from cookie to localStorage if LS is empty', () => {
        document.cookie = 'visitor_id=test-id-from-cookie';

        initializeVisitorId();

        expect(localStorage.getItem(LOCAL_STORAGE_KEY)).toBe('test-id-from-cookie');
    });

    it('does not overwrite localStorage if already set', () => {
        localStorage.setItem(LOCAL_STORAGE_KEY, 'existing-id');
        document.cookie = 'visitor_id=new-id';

        initializeVisitorId();

        // syncVisitorId is called, but it shouldn't setItem if visitorId is already truthy
        expect(localStorage.getItem(LOCAL_STORAGE_KEY)).toBe('existing-id');
    });

    it('attaches X-Visitor-Id header to Inertia requests', () => {
        localStorage.setItem(LOCAL_STORAGE_KEY, 'test-visitor-id');

        let beforeHandler: any;
        vi.mocked(router.on).mockImplementation((event: string, handler: any) => {
            if (event === 'before') beforeHandler = handler;
            return vi.fn();
        });

        initializeVisitorId();

        const event = {
            detail: {
                visit: {
                    headers: {},
                },
            },
        };

        beforeHandler(event);

        expect(event.detail.visit.headers).toHaveProperty('X-Visitor-Id', 'test-visitor-id');
    });

    it('syncs again on router success', () => {
        let successHandler: any;
        vi.mocked(router.on).mockImplementation((event: string, handler: any) => {
            if (event === 'success') successHandler = handler;
            return vi.fn();
        });

        localStorage.removeItem(LOCAL_STORAGE_KEY);
        document.cookie = 'visitor_id=refreshed-id';

        initializeVisitorId();

        // Initial sync happened. Now simulate success.
        vi.clearAllMocks();
        localStorage.removeItem(LOCAL_STORAGE_KEY);

        successHandler();

        expect(localStorage.getItem(LOCAL_STORAGE_KEY)).toBe('refreshed-id');
    });
});
