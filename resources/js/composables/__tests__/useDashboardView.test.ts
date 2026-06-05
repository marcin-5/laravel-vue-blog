import { beforeEach, describe, expect, it, vi } from 'vitest';

const pageMock = {
    props: {
        auth: {
            user: {
                can: {
                    view_admin_stats: false,
                    view_blogger_stats: false,
                },
            },
        },
    },
};

vi.mock('@inertiajs/vue3', () => ({
    usePage: vi.fn(() => pageMock),
}));

import { useDashboardView } from '../useDashboardView';

describe('useDashboardView', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        // Reset singleton manually if possible, or just accept it's a singleton
        // For testing we might need to be careful.
        // Actually, currentView is a module-level ref.
    });

    it('initializes with default view based on permissions', () => {
        pageMock.props.auth.user.can.view_admin_stats = true;
        const { currentView } = useDashboardView();
        expect(currentView.value).toBe('admin');
    });

    it('returns available views based on permissions', () => {
        pageMock.props.auth.user.can.view_admin_stats = true;
        pageMock.props.auth.user.can.view_blogger_stats = true;
        const { availableViews } = useDashboardView();
        expect(availableViews.value).toContain('admin');
        expect(availableViews.value).toContain('blogger');
        expect(availableViews.value).toContain('user');
    });

    it('setView updates currentView if valid', () => {
        const { currentView, setView } = useDashboardView();
        setView('user');
        expect(currentView.value).toBe('user');
    });
});
