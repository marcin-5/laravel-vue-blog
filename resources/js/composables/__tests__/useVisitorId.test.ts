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
        vi.stubGlobal('localStorage', {
            getItem: vi.fn(),
            setItem: vi.fn(),
        });
        vi.stubGlobal('document', {
            cookie: '',
        });
    });

    it('syncs visitor_id from cookie to localStorage if LS is empty', () => {
        vi.mocked(localStorage.getItem).mockReturnValue(null);
        vi.stubGlobal('document', {
            cookie: 'visitor_id=test-id-from-cookie',
        });

        initializeVisitorId();

        expect(localStorage.setItem).toHaveBeenCalledWith(LOCAL_STORAGE_KEY, 'test-id-from-cookie');
    });

    it('does not overwrite localStorage if already set', () => {
        vi.mocked(localStorage.getItem).mockReturnValue('existing-id');
        vi.stubGlobal('document', {
            cookie: 'visitor_id=new-id',
        });

        initializeVisitorId();

        // syncVisitorId is called, but it shouldn't setItem if visitorId is already truthy
        expect(localStorage.setItem).not.toHaveBeenCalled();
    });

    it('attaches X-Visitor-Id header to Inertia requests', () => {
        vi.mocked(localStorage.getItem).mockReturnValue('test-visitor-id');
        
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

        vi.mocked(localStorage.getItem).mockReturnValue(null);
        vi.stubGlobal('document', {
            cookie: 'visitor_id=refreshed-id',
        });

        initializeVisitorId();
        
        // Initial sync happened. Now simulate success.
        vi.clearAllMocks();
        vi.mocked(localStorage.getItem).mockReturnValue(null);
        
        successHandler();

        expect(localStorage.setItem).toHaveBeenCalledWith(LOCAL_STORAGE_KEY, 'refreshed-id');
    });
});
