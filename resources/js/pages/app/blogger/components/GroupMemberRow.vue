<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { GroupMember, GroupRole } from '@/types/group-members.types';
import { GROUP_MEMBER_ROLES } from '@/types/group-members.types';
import type { AcceptableValue } from 'reka-ui';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    member: GroupMember;
}>();

const emit = defineEmits<{
    roleChange: [member: GroupMember, role: GroupRole];
    remove: [member: GroupMember];
}>();

const { t } = useI18n();

function isGroupRole(value: AcceptableValue | undefined): value is GroupRole {
    return typeof value === 'string' && GROUP_MEMBER_ROLES.includes(value as GroupRole);
}

function handleRoleChange(value: AcceptableValue | undefined): void {
    if (!isGroupRole(value)) {
        return;
    }

    emit('roleChange', props.member, value);
}
</script>

<template>
    <tr class="border-b last:border-b-0 dark:border-sidebar-border">
        <td class="py-2 pr-2">{{ props.member.email }}</td>
        <td class="py-2 pr-2">{{ props.member.name }}</td>
        <td class="py-2 pr-2">{{ props.member.joined_at }}</td>
        <td class="py-2 pr-2">
            <Select :model-value="props.member.role" @update:model-value="handleRoleChange">
                <SelectTrigger class="w-40">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem v-for="role in GROUP_MEMBER_ROLES" :key="role" :value="role">
                        {{ t(`list.roles.${role}`) }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </td>
        <td class="py-2 pr-2 text-right">
            <Button variant="destructive" @click="emit('remove', props.member)">{{ t('list.remove') }}</Button>
        </td>
    </tr>
</template>
