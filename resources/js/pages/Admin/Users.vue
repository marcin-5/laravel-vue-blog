<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

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
        title: 'Users',
        href: '/admin/users',
    },
];

const editableUsers = ref<UserRow[]>(props.users ? props.users.map((u) => ({ ...u })) : []);
const originalById = ref(new Map<number, UserRow>());
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

function canEditQuota(user: UserRow): boolean {
    if (!props.currentUserIsAdmin) return false;
    const original = originalById.value.get(user.id);
    return !!original && (original.role === 'blogger' || original.role === 'admin');
}

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

// User creation
const newUser = ref<{ name: string; email: string; password: string; role: Role; blog_quota: number | null }>({
    name: '',
    email: '',
    password: '',
    role: 'user',
    blog_quota: 0,
});

const canEditNewQuota = computed(() => props.currentUserIsAdmin && (newUser.value.role === 'blogger' || newUser.value.role === 'admin'));

function submitCreate() {
    const payload: Record<string, unknown> = {
        name: newUser.value.name,
        email: newUser.value.email,
        password: newUser.value.password,
        role: newUser.value.role,
    };
    if (canEditNewQuota.value) {
        payload.blog_quota = newUser.value.blog_quota ?? (newUser.value.role === 'blogger' ? 1 : 0);
    }
    router.post(route('admin.users.store'), payload, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            newUser.value = { name: '', email: '', password: '', role: 'user', blog_quota: 0 };
        },
    });
}
</script>

<template>
    <Head :title="$t('users.title')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="relative flex-1 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                <h2 class="mb-4 text-lg font-semibold">{{ $t('users.heading') }}</h2>

                <!-- Create user form -->
                <div class="mb-6 rounded-md border border-dashed p-4">
                    <div class="mb-2 text-sm font-medium">{{ $t('users.create.title') }}</div>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-6">
                        <input
                            v-model="newUser.name"
                            :placeholder="$t('users.create.name')"
                            class="rounded-md border bg-background px-2 py-1 text-foreground"
                            type="text"
                        />
                        <input
                            v-model="newUser.email"
                            :placeholder="$t('users.create.email')"
                            class="rounded-md border bg-background px-2 py-1 text-foreground"
                            type="email"
                        />
                        <input
                            v-model="newUser.password"
                            :placeholder="$t('users.create.password')"
                            class="rounded-md border bg-background px-2 py-1 text-foreground"
                            type="password"
                        />
                        <select v-model="newUser.role" class="rounded-md border bg-background px-2 py-1 text-foreground">
                            <option v-for="r in roles" :key="r" :value="r">{{ $t('users.roles.' + r) }}</option>
                        </select>
                        <input
                            v-model.number="newUser.blog_quota"
                            :disabled="!canEditNewQuota"
                            :placeholder="$t('users.create.blog_quota')"
                            class="rounded-md border bg-background px-2 py-1 text-foreground"
                            min="0"
                            type="number"
                        />
                        <div>
                            <Button size="sm" type="button" variant="constructive" @click="submitCreate">{{ $t('users.actions.create') }}</Button>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-sidebar-border/70 text-xs text-muted-foreground uppercase dark:border-sidebar-border">
                            <tr>
                                <th class="py-2 pr-4">{{ $t('users.table.name') }}</th>
                                <th class="py-2 pr-4">{{ $t('users.table.email') }}</th>
                                <th class="py-2 pr-4">{{ $t('users.table.role') }}</th>
                                <th class="py-2 pr-4">{{ $t('users.table.blog_quota') }}</th>
                                <th class="py-2 pr-4">{{ $t('users.table.actions') }}</th>
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
                                        <option v-for="r in roles" :key="r" :value="r">{{ $t('users.roles.' + r) }}</option>
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
                                        {{ $t('users.actions.save') }}
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
