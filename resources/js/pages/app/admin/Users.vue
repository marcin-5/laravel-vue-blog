<script lang="ts" setup>
import UserCreateForm from '@/components/admin/UserCreateForm.vue';
import UserList from '@/components/admin/UserList.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Role, UserRow } from '@/types/admin.types';
import { type BreadcrumbItem } from '@/types';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

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
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="relative flex-1 rounded-xl border border-sidebar-border/70 p-4">
                <h2 class="mb-4 text-lg font-semibold">{{ $t('admin.users.heading') }}</h2>

                <!-- Create user form -->
                <UserCreateForm :current-user-is-admin="props.currentUserIsAdmin" :roles="roles" />

                <UserList :current-user-is-admin="props.currentUserIsAdmin" :roles="roles" :users="props.users ?? []" />
            </div>
        </div>
    </AppLayout>
</template>
