import type { GroupMember, GroupMemberFilters } from '@/types/group-members.types';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useGroupMembers } from '../useGroupMembers';

const mocks = vi.hoisted(() => ({
    get: vi.fn(),
    post: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
    visit: vi.fn(),
    route: vi.fn((name: string, params?: Record<string, number> | string) => {
        if (typeof params === 'string') {
            return `/${name}/${params}`;
        }

        if (params) {
            return `/${name}/${params.group}/${params.user}`;
        }

        return `/${name}`;
    }),
}));

vi.mock('@inertiajs/vue3', () => ({
    router: {
        get: mocks.get,
        post: mocks.post,
        patch: mocks.patch,
        delete: mocks.delete,
        visit: mocks.visit,
    },
}));

vi.stubGlobal('route', mocks.route);

const filters: GroupMemberFilters = {
    group_id: 4,
    owner_id: 7,
    per_page: 25,
    sort_by: 'email',
    sort_dir: 'asc',
};

const member: GroupMember = {
    id: 12,
    name: 'Jane Doe',
    email: 'jane@example.com',
    group_id: 4,
    role: 'member',
    joined_at: '2026-08-10',
};

describe('useGroupMembers', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('builds the query from filters and excludes the all-group value', () => {
        const state = useGroupMembers({ filters, isAdmin: true });

        expect(state.query.value).toEqual({
            group_id: '4',
            owner_id: '7',
            per_page: '25',
            sort_by: 'email',
            sort_dir: 'asc',
        });

        state.changeGroup(undefined);

        expect(state.query.value).toEqual({
            owner_id: '7',
            per_page: '25',
            sort_by: 'email',
            sort_dir: 'asc',
        });
    });

    it('resets the group and reloads when the owner changes', () => {
        const state = useGroupMembers({ filters, isAdmin: true });

        state.changeOwner('9');

        expect(state.groupId.value).toBe('all');
        expect(mocks.get).toHaveBeenCalledWith(
            '/blogger.groups.members.index',
            {
                owner_id: '9',
                per_page: '25',
                sort_by: 'email',
                sort_dir: 'asc',
            },
            { preserveState: true, replace: true },
        );
    });

    it('ignores owner filters for non-administrators and rejects invalid sort values', () => {
        const state = useGroupMembers({ filters, isAdmin: false });

        expect(state.query.value).not.toHaveProperty('owner_id');
        state.changeSortBy('invalid');
        state.changeSortDir('invalid');

        expect(mocks.get).not.toHaveBeenCalled();
        expect(state.sortBy.value).toBe('email');
        expect(state.sortDir.value).toBe('asc');
    });

    it('uses named routes and preserves scroll for member actions', () => {
        const state = useGroupMembers({ filters, isAdmin: true });
        const onSuccess = vi.fn();

        state.addMember({ email: 'new@example.com', role: 'contributor' }, onSuccess);
        state.changeRole(member, 'moderator');
        state.removeMember(member);

        expect(mocks.post).toHaveBeenCalledWith(
            '/blogger.groups.members.store/4',
            { email: 'new@example.com', role: 'contributor' },
            { preserveScroll: true, onSuccess },
        );
        expect(mocks.patch).toHaveBeenCalledWith('/blogger.groups.members.update/4/12', { role: 'moderator' }, { preserveScroll: true });
        expect(mocks.delete).toHaveBeenCalledWith('/blogger.groups.members.destroy/4/12', { preserveScroll: true });
    });

    it('does not add a member to all groups and visits only valid pagination URLs', () => {
        const state = useGroupMembers({ filters: { ...filters, group_id: null }, isAdmin: true });

        state.addMember({ email: 'new@example.com', role: 'member' });
        state.visitPage(null);
        state.visitPage('/groups/members?page=2');

        expect(mocks.post).not.toHaveBeenCalled();
        expect(mocks.visit).toHaveBeenCalledWith('/groups/members?page=2', { preserveState: true });
    });
});
