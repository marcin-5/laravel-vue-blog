<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useUserPermissions } from '@/composables/useUserPermissions';
import type { Role, UserRow as UserRowType, UserWithQuota } from '@/types/admin.types';
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

interface SavePayload {
    role: Role;
    blog_quota?: number;
    [key: string]: any;
}

const props = defineProps<{
    currentUserIsAdmin?: boolean;
    roles: Role[];
    user: UserRowType;
}>();

const editableUser = ref<UserRowType>(cloneUser(props.user));
const originalById = ref(new Map<number, UserWithQuota>());
const isSaving = ref(false);

resetOriginal(props.user);

const { canEditQuota } = useUserPermissions({
    currentUserIsAdmin: props.currentUserIsAdmin,
    originalsById: originalById,
});

const canEditUserQuota = computed(() => canEditQuota(editableUser.value));
const hasChanges = computed(() => {
    const original = originalById.value.get(editableUser.value.id);

    if (!original) {
        return false;
    }

    if (editableUser.value.role !== original.role) {
        return true;
    }

    return canEditUserQuota.value && getQuotaOrDefault(editableUser.value.blog_quota) !== getQuotaOrDefault(original.blog_quota);
});

function cloneUser(user: UserRowType): UserRowType {
    return { ...user };
}

function getQuotaOrDefault(quota: number | null): number {
    return quota ?? 0;
}

function resetOriginal(user: UserRowType): void {
    originalById.value = new Map([[user.id, { ...user }]]);
}

function resetUser(user: UserRowType): void {
    editableUser.value = cloneUser(user);
    resetOriginal(user);
}

function buildSavePayload(): SavePayload {
    const payload: SavePayload = { role: editableUser.value.role };

    if (canEditUserQuota.value) {
        payload.blog_quota = getQuotaOrDefault(editableUser.value.blog_quota);
    }

    return payload;
}

function saveUser(): void {
    router.patch(route('admin.users.update', editableUser.value.id), buildSavePayload(), {
        preserveScroll: true,
        preserveState: true,
        onStart: () => {
            isSaving.value = true;
        },
        onFinish: () => {
            isSaving.value = false;
        },
    });
}

watch(
    () => props.user,
    (user) => {
        resetUser(user);
    },
);
</script>

<template>
    <tr class="last:border-b-0 dark:border-sidebar-border">
        <td class="py-2 pr-4">{{ editableUser.name }}</td>
        <td class="py-2 pr-4">{{ editableUser.email }}</td>
        <td class="py-2 pr-4">
            <Select v-model="editableUser.role">
                <SelectTrigger class="h-8 w-30">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem v-for="role in props.roles" :key="role" :value="role">
                        {{ t('admin.users.roles.' + role) }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </td>
        <td class="py-2 pr-4">
            <input
                v-model.number="editableUser.blog_quota"
                :disabled="!canEditUserQuota"
                class="w-24 rounded-md border bg-background px-2 py-1 text-foreground"
                min="0"
                type="number"
            />
        </td>
        <td class="py-2 pr-4">
            <Button :disabled="!hasChanges || isSaving" :variant="hasChanges ? 'constructive' : 'muted'" size="sm" type="button" @click="saveUser">
                {{ t('admin.users.actions.save') }}
            </Button>
        </td>
    </tr>
</template>
