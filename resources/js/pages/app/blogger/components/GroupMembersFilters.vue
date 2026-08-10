<script lang="ts" setup>
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { GroupMemberSortDirection, GroupMemberSortField, GroupOwner, SimpleGroup } from '@/types/group-members.types';
import type { AcceptableValue } from 'reka-ui';
import { useI18n } from 'vue-i18n';

export interface GroupMembersFilterValues {
    groupId: string;
    ownerId: string | null;
    perPage: string;
    sortBy: GroupMemberSortField;
    sortDir: GroupMemberSortDirection;
}

const props = defineProps<{
    filters: GroupMembersFilterValues;
    groups: SimpleGroup[];
    isAdmin: boolean;
    owners?: GroupOwner[];
}>();

const emit = defineEmits<{
    ownerChange: [value: AcceptableValue | undefined];
    groupChange: [value: AcceptableValue | undefined];
    perPageChange: [value: AcceptableValue | undefined];
    sortByChange: [value: AcceptableValue | undefined];
    sortDirChange: [value: AcceptableValue | undefined];
}>();

const { t } = useI18n();
</script>

<template>
    <div class="grid grid-cols-1 gap-3 md:grid-cols-5">
        <div v-if="props.isAdmin">
            <label class="mb-1 block text-sm text-muted-foreground">{{ t('list.owner') }}</label>
            <Select :model-value="props.filters.ownerId" @update:model-value="emit('ownerChange', $event)">
                <SelectTrigger>
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem v-for="owner in props.owners ?? []" :key="owner.id" :value="String(owner.id)">
                        {{ owner.name || owner.email }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div>
            <label class="mb-1 block text-sm text-muted-foreground">{{ t('list.group') }}</label>
            <Select :model-value="props.filters.groupId" @update:model-value="emit('groupChange', $event)">
                <SelectTrigger>
                    <SelectValue :placeholder="t('list.all_groups')" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">{{ t('list.all_groups') }}</SelectItem>
                    <SelectItem v-for="group in props.groups" :key="group.id" :value="String(group.id)">
                        {{ group.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div>
            <label class="mb-1 block text-sm text-muted-foreground">{{ t('list.per_page') }}</label>
            <Select :model-value="props.filters.perPage" @update:model-value="emit('perPageChange', $event)">
                <SelectTrigger>
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="10">10</SelectItem>
                    <SelectItem value="25">25</SelectItem>
                    <SelectItem value="all">{{ t('list.all_per_page') }}</SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div>
            <label class="mb-1 block text-sm text-muted-foreground">{{ t('list.sort_by') }}</label>
            <Select :model-value="props.filters.sortBy" @update:model-value="emit('sortByChange', $event)">
                <SelectTrigger>
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="email">{{ t('list.email') }}</SelectItem>
                    <SelectItem value="name">{{ t('list.username') }}</SelectItem>
                    <SelectItem value="joined_at">{{ t('list.joined_at') }}</SelectItem>
                    <SelectItem value="role">{{ t('list.role') }}</SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div>
            <label class="mb-1 block text-sm text-muted-foreground">{{ t('list.direction') }}</label>
            <Select :model-value="props.filters.sortDir" @update:model-value="emit('sortDirChange', $event)">
                <SelectTrigger>
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="asc">{{ t('list.asc') }}</SelectItem>
                    <SelectItem value="desc">{{ t('list.desc') }}</SelectItem>
                </SelectContent>
            </Select>
        </div>
    </div>
</template>
