<script lang="ts" setup>
import UserCreateForm from '@/components/admin/UserCreateForm.vue';
import { Button } from '@/components/ui/button';
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

const editableUsers = ref<UserRow[]>(props.users ? props.users.map((u) => ({ ...u })) : []);
const originalById = ref(new Map<number, UserWithQuota>());
function setOriginals(users?: UserRow[]) {
    originalById.value = new Map((users ?? []).map((u) => [u.id, { ...u }]));
}
setOriginals(props.users);
watch(
    () => props.users,
    (users) => {
        editableUsers.value = users ? users.map((u) => ({ ...u })) : [];
        setOriginals(users);
    },
);

const roles: Role[] = ['admin', 'blogger', 'user'];

const { canEditQuota } = useUserPermissions({
    currentUserIsAdmin: props.currentUserIsAdmin,
    originalsById: originalById,
});

function isChanged(user: UserRow): boolean {
    const original = originalById.value.get(user.id);
    if (!original) return false;
    if (user.role !== original.role) return true;
    if (canEditQuota(user)) {
        const current = user.blog_quota ?? 0;
        const orig = original.blog_quota ?? 0;
        return current !== orig;
    }
    return false;
}

function saveUser(user: UserRow) {
    const payload: Record<string, unknown> = {
        role: user.role,
    };
    if (canEditQuota(user)) {
        payload.blog_quota = user.blog_quota ?? 0;
    }
    router.patch(route('admin.users.update', user.id), payload, {
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
                                    <select v-model="u.role" class="rounded-md border bg-background px-2 py-1 text-foreground">
                                        <option v-for="r in roles" :key="r" :value="r">{{ $t('admin.users.roles.' + r) }}</option>
                                    </select>
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
