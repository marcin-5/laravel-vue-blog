<script lang="ts" setup>
import UserCreateForm from '@/components/admin/UserCreateForm.vue';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { type UserWithQuota, useUserPermissions } from '@/composables/useUserPermissions';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

// Types
export type Role = 'admin' | 'blogger' | 'user';

export interface UserRow {
    id: number;
    name: string;
    email: string;
    role: Role;
    blog_quota: number | null;
}

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
    <Head :title="$t('admin.users.title')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="relative flex-1 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                <h2 class="mb-4 text-lg font-semibold">{{ $t('admin.users.heading') }}</h2>

                <!-- Create user form -->
                <UserCreateForm :current-user-is-admin="props.currentUserIsAdmin" :roles="roles" />

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-sidebar-border/70 text-xs text-muted-foreground uppercase dark:border-sidebar-border">
                            <tr>
                                <th class="py-2 pr-4">{{ $t('admin.users.table.name') }}</th>
                                <th class="py-2 pr-4">{{ $t('admin.users.table.email') }}</th>
                                <th class="py-2 pr-4">{{ $t('admin.users.table.role') }}</th>
                                <th class="py-2 pr-4">{{ $t('admin.users.table.blog_quota') }}</th>
                                <th class="py-2 pr-4">{{ $t('admin.users.table.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="u in editableUsers"
                                :key="u.id"
                                class="border-b border-sidebar-border/70 last:border-b-0 dark:border-sidebar-border"
                            >
                                <td class="py-2 pr-4">{{ u.name }}</td>
                                <td class="py-2 pr-4">{{ u.email }}</td>
                                <td class="py-2 pr-4">
                                    <Select v-model="u.role">
                                        <SelectTrigger class="h-8 w-[120px]">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="r in roles" :key="r" :value="r">{{ $t('admin.users.roles.' + r) }}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </td>
                                <td class="py-2 pr-4">
                                    <input
                                        v-model.number="u.blog_quota"
                                        :disabled="!canEditQuota(u)"
                                        class="w-24 rounded-md border bg-background px-2 py-1 text-foreground"
                                        min="0"
                                        type="number"
                                    />
                                </td>
                                <td class="py-2 pr-4">
                                    <Button
                                        :disabled="!isChanged(u)"
                                        :variant="isChanged(u) ? 'constructive' : 'muted'"
                                        size="sm"
                                        type="button"
                                        @click="saveUser(u)"
                                    >
                                        {{ $t('admin.users.actions.save') }}
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
