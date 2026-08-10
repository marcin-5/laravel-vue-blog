<script lang="ts" setup>
import UserCreateForm from '@/components/admin/UserCreateForm.vue';
import UserList from '@/components/admin/UserList.vue';
import { type UserWithQuota, useUserPermissions } from '@/composables/useUserPermissions';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Role, UserRow } from '@/types/admin.types';
import { type BreadcrumbItem } from '@/types';
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

interface SavePayload {
    role: Role;
    blog_quota?: number;
    [key: string]: any;
}

interface Props {
    users?: UserRow[];
    currentUserIsAdmin?: boolean;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: t('admin.users.breadcrumb'),
        href: '/admin/users',
    },
];

const roles: Role[] = ['admin', 'blogger', 'user'];

// --- Helpers ---

function cloneUsers(users?: UserRow[]): UserRow[] {
    return users ? users.map((u) => ({ ...u })) : [];
}

function getQuotaOrDefault(quota: number | null): number {
    return quota ?? 0;
}

function resetOriginals(users?: UserRow[]) {
    originalById.value = new Map((users ?? []).map((u) => [u.id, { ...u }]));
}

function buildSavePayload(user: UserRow): SavePayload {
    const payload: SavePayload = { role: user.role };

    if (canEditQuota(user)) {
        payload.blog_quota = getQuotaOrDefault(user.blog_quota);
    }

    return payload;
}

// --- State ---

const editableUsers = ref<UserRow[]>(cloneUsers(props.users));
const originalById = ref(new Map<number, UserWithQuota>());
resetOriginals(props.users);

watch(
    () => props.users,
    (users) => {
        editableUsers.value = cloneUsers(users);
        resetOriginals(users);
    },
);

const { canEditQuota } = useUserPermissions({
    currentUserIsAdmin: props.currentUserIsAdmin,
    originalsById: originalById,
});

// --- Actions ---

function isChanged(user: UserRow): boolean {
    const original = originalById.value.get(user.id);
    if (!original) return false;

    if (user.role !== original.role) return true;

    if (canEditQuota(user)) {
        return getQuotaOrDefault(user.blog_quota) !== getQuotaOrDefault(original.blog_quota);
    }

    return false;
}

function saveUser(user: UserRow) {
    router.patch(route('admin.users.update', user.id), buildSavePayload(user), {
        preserveScroll: true,
        preserveState: true,
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="relative flex-1 rounded-xl border border-sidebar-border/70 p-4">
                <h2 class="mb-4 text-lg font-semibold">{{ $t('admin.users.heading') }}</h2>

                <!-- Create user form -->
                <UserCreateForm :current-user-is-admin="props.currentUserIsAdmin" :roles="roles" />

                <UserList
                    :can-edit-quota="canEditQuota"
                    :is-changed="isChanged"
                    :roles="roles"
                    :save-user="saveUser"
                    :users="editableUsers"
                />
            </div>
        </div>
    </AppLayout>
</template>
