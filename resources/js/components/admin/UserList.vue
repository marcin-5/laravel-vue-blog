<script lang="ts" setup>
import UserRow from '@/components/admin/UserRow.vue';
import type { Role, UserRow as UserRowType } from '@/types/admin.types';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps<{
    currentUserIsAdmin?: boolean;
    roles: Role[];
    users: UserRowType[];
}>();
</script>

<template>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-sidebar-border/70 text-xs text-muted-foreground uppercase">
                <tr>
                    <th class="py-2 pr-4">{{ t('admin.users.table.name') }}</th>
                    <th class="py-2 pr-4">{{ t('admin.users.table.email') }}</th>
                    <th class="py-2 pr-4">{{ t('admin.users.table.role') }}</th>
                    <th class="py-2 pr-4">{{ t('admin.users.table.blog_quota') }}</th>
                    <th class="py-2 pr-4">{{ t('admin.users.table.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                <UserRow
                    v-for="user in props.users"
                    :key="user.id"
                    :current-user-is-admin="props.currentUserIsAdmin"
                    :roles="props.roles"
                    :user="user"
                />
            </tbody>
        </table>
    </div>
</template>
