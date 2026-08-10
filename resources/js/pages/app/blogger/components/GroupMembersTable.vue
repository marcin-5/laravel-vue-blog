<script lang="ts" setup>
import GroupMemberRow from '@/pages/app/blogger/components/GroupMemberRow.vue';
import type { GroupMember, GroupRole } from '@/types/group-members.types';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    members: GroupMember[];
}>();

const emit = defineEmits<{
    roleChange: [member: GroupMember, role: GroupRole];
    remove: [member: GroupMember];
}>();

const { t } = useI18n();

function handleRoleChange(member: GroupMember, role: GroupRole): void {
    emit('roleChange', member, role);
}
</script>

<template>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b">
                <tr>
                    <th class="py-2 pr-2">{{ t('list.email') }}</th>
                    <th class="py-2 pr-2">{{ t('list.username') }}</th>
                    <th class="py-2 pr-2">{{ t('list.joined_at') }}</th>
                    <th class="py-2 pr-2">{{ t('list.role') }}</th>
                    <th class="py-2 pr-2"></th>
                </tr>
            </thead>
            <tbody>
                <GroupMemberRow
                    v-for="member in props.members"
                    :key="member.id"
                    :member="member"
                    @remove="emit('remove', $event)"
                    @role-change="handleRoleChange"
                />
                <tr v-if="props.members.length === 0">
                    <td class="py-4 text-center text-muted-foreground" colspan="5">{{ t('list.no_results') }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
