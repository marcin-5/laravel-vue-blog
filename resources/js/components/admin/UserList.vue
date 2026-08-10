<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { UserRow, Role, UserWithQuota } from '@/types/admin.types';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps<{
    users: UserRow[];
    roles: Role[];
    canEditQuota: (user: UserWithQuota) => boolean;
    isChanged: (user: UserRow) => boolean;
    saveUser: (user: UserRow) => void;
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
                <tr
                    v-for="user in props.users"
                    :key="user.id"
                    class="border-b border-sidebar-border/70 last:border-b-0"
                >
                    <td class="py-2 pr-4">{{ user.name }}</td>
                    <td class="py-2 pr-4">{{ user.email }}</td>
                    <td class="py-2 pr-4">
                        <Select v-model="user.role">
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
                            v-model.number="user.blog_quota"
                            :disabled="!props.canEditQuota(user)"
                            class="w-24 rounded-md border bg-background px-2 py-1 text-foreground"
                            min="0"
                            type="number"
                        />
                    </td>
                    <td class="py-2 pr-4">
                        <Button
                            :disabled="!props.isChanged(user)"
                            :variant="props.isChanged(user) ? 'constructive' : 'muted'"
                            size="sm"
                            type="button"
                            @click="props.saveUser(user)"
                        >
                            {{ t('admin.users.actions.save') }}
                        </Button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>