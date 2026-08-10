<script lang="ts" setup>
import AddGroupMemberForm from '@/pages/app/blogger/components/AddGroupMemberForm.vue';
import GroupMembersFilters from '@/pages/app/blogger/components/GroupMembersFilters.vue';
import GroupMembersPagination from '@/pages/app/blogger/components/GroupMembersPagination.vue';
import GroupMembersTable from '@/pages/app/blogger/components/GroupMembersTable.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, Pagination } from '@/types';
import type { GroupMember, GroupMemberFilters, GroupOwner, GroupRole, SimpleGroup } from '@/types/group-members.types';
import { useGroupMembers } from '@/composables/useGroupMembers';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    filters: GroupMemberFilters;
    groups: SimpleGroup[];
    isAdmin: boolean;
    members: GroupMember[];
    pagination?: Pagination;
    owners?: GroupOwner[];
}>();

const { t } = useI18n();

const {
    groupId,
    ownerId,
    perPage,
    sortBy,
    sortDir,
    changeGroup,
    changeOwner,
    changePerPage,
    changeRole,
    changeSortBy,
    changeSortDir,
    addMember,
    removeMember,
    visitPage,
} = useGroupMembers({ filters: props.filters, isAdmin: props.isAdmin });

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('blogger.breadcrumb.dashboard'), href: route('dashboard') },
    { title: t('blogger.breadcrumb.groups_members'), href: route('blogger.groups.members.index') },
];

function handleAddMember(payload: Parameters<typeof addMember>[0], onSuccess: () => void): void {
    addMember(payload, onSuccess);
}

function handleRoleChange(member: GroupMember, role: GroupRole): void {
    changeRole(member, role);
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <GroupMembersFilters
                :filters="{ groupId, ownerId, perPage, sortBy, sortDir }"
                :groups="props.groups"
                :is-admin="props.isAdmin"
                :owners="props.owners"
                @group-change="changeGroup"
                @owner-change="changeOwner"
                @per-page-change="changePerPage"
                @sort-by-change="changeSortBy"
                @sort-dir-change="changeSortDir"
            />
            <AddGroupMemberForm :group-id="groupId" @submit="handleAddMember" />
            <GroupMembersTable :members="props.members" @remove="removeMember" @role-change="handleRoleChange" />
            <GroupMembersPagination :pagination="props.pagination" @visit-page="visitPage" />
        </div>
    </AppLayout>
</template>
