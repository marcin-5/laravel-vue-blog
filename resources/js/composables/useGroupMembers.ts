import type {
    AddGroupMemberPayload,
    ChangeGroupMemberRolePayload,
    GroupMember,
    GroupMemberFilters,
    GroupMemberSortDirection,
    GroupMemberSortField,
    GroupMembersQuery,
} from '@/types/group-members.types';
import { GROUP_MEMBER_SORT_FIELDS } from '@/types/group-members.types';
import { router } from '@inertiajs/vue3';
import type { AcceptableValue } from 'reka-ui';
import { computed, readonly, shallowRef } from 'vue';

export interface UseGroupMembersOptions {
    filters: GroupMemberFilters;
    isAdmin: boolean;
}

export function useGroupMembers(options: UseGroupMembersOptions) {
    const groupId = shallowRef(options.filters.group_id ? String(options.filters.group_id) : 'all');
    const perPage = shallowRef(String(options.filters.per_page ?? 10));
    const sortBy = shallowRef<GroupMemberSortField>(options.filters.sort_by ?? 'email');
    const sortDir = shallowRef<GroupMemberSortDirection>(options.filters.sort_dir ?? 'asc');
    const ownerId = shallowRef<string | null>(options.filters.owner_id ? String(options.filters.owner_id) : null);

    const query = computed<GroupMembersQuery>(() => ({
        ...(groupId.value !== 'all' ? { group_id: groupId.value } : {}),
        per_page: perPage.value,
        sort_by: sortBy.value,
        sort_dir: sortDir.value,
        ...(options.isAdmin && ownerId.value ? { owner_id: ownerId.value } : {}),
    }));

    function normalizeSelectValue(value: AcceptableValue | undefined): string | null {
        return value == null ? null : String(value);
    }

    function isSortField(value: string | null): value is GroupMemberSortField {
        return value !== null && GROUP_MEMBER_SORT_FIELDS.includes(value as GroupMemberSortField);
    }

    function reload(): void {
        router.get(route('blogger.groups.members.index'), query.value, {
            preserveState: true,
            replace: true,
        });
    }

    function changeOwner(value: AcceptableValue | undefined): void {
        ownerId.value = normalizeSelectValue(value);
        groupId.value = 'all';
        reload();
    }

    function changeGroup(value: AcceptableValue | undefined): void {
        groupId.value = normalizeSelectValue(value) ?? 'all';
        reload();
    }

    function changePerPage(value: AcceptableValue | undefined): void {
        perPage.value = normalizeSelectValue(value) ?? '10';
        reload();
    }

    function changeSortBy(value: AcceptableValue | undefined): void {
        const normalized = normalizeSelectValue(value);

        if (!isSortField(normalized)) {
            return;
        }

        sortBy.value = normalized;
        reload();
    }

    function changeSortDir(value: AcceptableValue | undefined): void {
        const normalized = normalizeSelectValue(value);

        if (normalized !== 'asc' && normalized !== 'desc') {
            return;
        }

        sortDir.value = normalized;
        reload();
    }

    function addMember(payload: AddGroupMemberPayload, onSuccess?: () => void): void {
        if (groupId.value === 'all') {
            return;
        }

        router.post(route('blogger.groups.members.store', groupId.value), payload, {
            preserveScroll: true,
            onSuccess,
        });
    }

    function changeRole(member: GroupMember, role: GroupMember['role']): void {
        const payload: ChangeGroupMemberRolePayload = { role };

        router.patch(route('blogger.groups.members.update', { group: member.group_id, user: member.id }), payload, { preserveScroll: true });
    }

    function removeMember(member: GroupMember): void {
        router.delete(route('blogger.groups.members.destroy', { group: member.group_id, user: member.id }), {
            preserveScroll: true,
        });
    }

    function visitPage(url: string | null): void {
        if (!url) {
            return;
        }

        router.visit(url, { preserveState: true });
    }

    return {
        groupId: readonly(groupId),
        perPage: readonly(perPage),
        sortBy: readonly(sortBy),
        sortDir: readonly(sortDir),
        ownerId: readonly(ownerId),
        query,
        reload,
        changeOwner,
        changeGroup,
        changePerPage,
        changeSortBy,
        changeSortDir,
        addMember,
        changeRole,
        removeMember,
        visitPage,
    };
}
