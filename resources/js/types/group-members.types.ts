import type { FormDataConvertible } from '@inertiajs/core';

export const GROUP_MEMBER_ROLES = ['member', 'contributor', 'moderator', 'maintainer'] as const;

export type GroupRole = (typeof GROUP_MEMBER_ROLES)[number];

export const GROUP_MEMBER_SORT_FIELDS = ['email', 'name', 'joined_at', 'role'] as const;

export type GroupMemberSortField = (typeof GROUP_MEMBER_SORT_FIELDS)[number];

export type GroupMemberSortDirection = 'asc' | 'desc';

export type GroupMemberPerPage = number | '10' | '25' | 'all';

export interface GroupMember {
    id: number;
    name: string;
    email: string;
    group_id: number;
    role: GroupRole;
    joined_at: string;
}

export interface GroupMemberFilters {
    group_id?: number | null;
    per_page: GroupMemberPerPage;
    sort_by: GroupMemberSortField;
    sort_dir: GroupMemberSortDirection;
    owner_id?: number | null;
}

export type GroupMembersQuery = Record<string, FormDataConvertible> & {
    group_id?: string;
    per_page: string;
    sort_by: GroupMemberSortField;
    sort_dir: GroupMemberSortDirection;
    owner_id?: string;
};

export interface GroupOwner {
    id: number;
    name: string;
    email: string;
}

export interface SimpleGroup {
    id: number;
    name: string;
}

export type AddGroupMemberPayload = Record<string, FormDataConvertible> & {
    email: string;
    role: GroupRole;
};

export type ChangeGroupMemberRolePayload = Record<string, FormDataConvertible> & {
    role: GroupRole;
};
