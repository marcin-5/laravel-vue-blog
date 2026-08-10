<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { AddGroupMemberPayload, GroupRole } from '@/types/group-members.types';
import { GROUP_MEMBER_ROLES } from '@/types/group-members.types';
import type { AcceptableValue } from 'reka-ui';
import { computed, shallowRef } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    groupId: string;
}>();

const emit = defineEmits<{
    submit: [payload: AddGroupMemberPayload, onSuccess: () => void];
}>();

const { t } = useI18n();
const email = shallowRef('');
const role = shallowRef<GroupRole>('member');

const canSubmit = computed(() => props.groupId !== 'all' && email.value.trim().length > 0);

function isGroupRole(value: AcceptableValue | undefined): value is GroupRole {
    return typeof value === 'string' && GROUP_MEMBER_ROLES.includes(value as GroupRole);
}

function handleRoleChange(value: AcceptableValue | undefined): void {
    if (!isGroupRole(value)) {
        return;
    }

    role.value = value;
}

function clearEmail(): void {
    email.value = '';
}

function handleSubmit(): void {
    if (!canSubmit.value) {
        return;
    }

    emit('submit', { email: email.value.trim(), role: role.value }, clearEmail);
}
</script>

<template>
    <form class="grid grid-cols-1 items-end gap-3 md:grid-cols-5" @submit.prevent="handleSubmit">
        <div class="md:col-span-2">
            <label class="mb-1 block text-sm text-muted-foreground">{{ t('list.email') }}</label>
            <Input v-model="email" :placeholder="t('list.email')" required type="email" />
        </div>
        <div>
            <label class="mb-1 block text-sm text-muted-foreground">{{ t('list.role') }}</label>
            <Select :model-value="role" @update:model-value="handleRoleChange">
                <SelectTrigger>
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem v-for="memberRole in GROUP_MEMBER_ROLES" :key="memberRole" :value="memberRole">
                        {{ t(`list.roles.${memberRole}`) }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>
        <div>
            <Button :disabled="!canSubmit" type="submit">{{ t('list.add') }}</Button>
        </div>
    </form>
</template>
