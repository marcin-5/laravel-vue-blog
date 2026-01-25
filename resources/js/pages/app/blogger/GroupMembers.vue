<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { AcceptableValue } from 'reka-ui';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

type SimpleGroup = { id: number; name: string };

const props = defineProps<{
    filters: { group_id?: number | null; per_page: number | string; sort_by: string; sort_dir: 'asc' | 'desc'; owner_id?: number };
    groups: SimpleGroup[];
    isAdmin: boolean;
    members: any; // Inertia pagination object
    owners?: { id: number; name: string; email: string }[];
}>();

const { t } = useI18n();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('blogger.breadcrumb.dashboard'), href: '/dashboard' },
    { title: t('blogger.breadcrumb.groups_members'), href: '/groups/members' },
];

const groupId = ref<string>(props.filters.group_id ? String(props.filters.group_id) : 'all');
const perPage = ref<string>(String(props.filters.per_page ?? 10));
const sortBy = ref<string>(props.filters.sort_by ?? 'email');
const sortDir = ref<'asc' | 'desc'>((props.filters.sort_dir as any) ?? 'asc');
// Reka Select emits AcceptableValue (string | number | null). Keep types compatible.
const ownerId = ref<string | null>(props.filters.owner_id ? String(props.filters.owner_id) : null);

const query = computed(() => ({
    group_id: groupId.value && groupId.value !== 'all' ? groupId.value : undefined,
    per_page: perPage.value,
    sort_by: sortBy.value,
    sort_dir: sortDir.value,
    ...(props.isAdmin && ownerId.value ? { owner_id: ownerId.value } : {}),
}));

function reload() {
    router.get('/groups/members', query.value, { preserveState: true, replace: true });
}

function onOwnerChange(val: AcceptableValue) {
    // Normalize AcceptableValue (may include bigint) to string|null
    ownerId.value = val == null ? null : String(val as any);
    // Changing owner should reset group selection
    groupId.value = 'all';
    reload();
}

// Actions
const addEmail = ref('');
const addRole = ref('member');

function addMember() {
    if (!groupId.value || groupId.value === 'all') return;
    router.post(
        `/groups/members/${groupId.value}` as any,
        { email: addEmail.value, role: addRole.value },
        { preserveScroll: true, onSuccess: () => (addEmail.value = '') },
    );
}

function changeRole(row: any, role: string) {
    router.patch(`/groups/members/${row.gu_group_id ?? row.group_id}/${row.id}` as any, { role }, { preserveScroll: true });
}

function removeMember(row: any) {
    router.delete(`/groups/members/${row.gu_group_id ?? row.group_id}/${row.id}` as any, { preserveScroll: true });
}
</script>

<template>
    <Head :title="$t('blogger.groups.members.title')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Filters -->
            <div class="grid grid-cols-1 gap-3 md:grid-cols-5">
                <div v-if="props.isAdmin">
                    <label class="mb-1 block text-sm text-muted-foreground">{{ $t('list.owner') }}</label>
                    <Select v-model="ownerId" @update:modelValue="onOwnerChange">
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="o in props.owners ?? []" :key="o.id" :value="String(o.id)">
                                {{ o.name || o.email }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div>
                    <label class="mb-1 block text-sm text-muted-foreground">{{ $t('list.group') }}</label>
                    <Select v-model="groupId" @update:modelValue="reload">
                        <SelectTrigger>
                            <SelectValue :placeholder="$t('list.all_groups')" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">{{ $t('list.all_groups') }}</SelectItem>
                            <SelectItem v-for="g in props.groups" :key="g.id" :value="String(g.id)">{{ g.name }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div>
                    <label class="mb-1 block text-sm text-muted-foreground">{{ $t('list.per_page') }}</label>
                    <Select v-model="perPage" @update:modelValue="reload">
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="10">10</SelectItem>
                            <SelectItem value="25">25</SelectItem>
                            <SelectItem value="all">{{ $t('list.all_per_page') }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div>
                    <label class="mb-1 block text-sm text-muted-foreground">{{ $t('list.sort_by') }}</label>
                    <Select v-model="sortBy" @update:modelValue="reload">
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="email">Email</SelectItem>
                            <SelectItem value="name">{{ $t('list.username') }}</SelectItem>
                            <SelectItem value="joined_at">{{ $t('list.joined_at') }}</SelectItem>
                            <SelectItem value="role">{{ $t('list.role') }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div>
                    <label class="mb-1 block text-sm text-muted-foreground">{{ $t('list.direction') }}</label>
                    <Select v-model="sortDir" @update:modelValue="reload">
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="asc">{{ $t('list.asc') }}</SelectItem>
                            <SelectItem value="desc">{{ $t('list.desc') }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <!-- Add member (moved below filters for UX) -->
            <div class="grid grid-cols-1 items-end gap-3 md:grid-cols-5">
                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm text-muted-foreground">{{ $t('list.email') }}</label>
                    <Input v-model="addEmail" :placeholder="$t('list.email')" type="email" />
                </div>
                <div>
                    <label class="mb-1 block text-sm text-muted-foreground">{{ $t('list.role') }}</label>
                    <Select v-model="addRole">
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="member">member</SelectItem>
                            <SelectItem value="contributor">contributor</SelectItem>
                            <SelectItem value="moderator">moderator</SelectItem>
                            <SelectItem value="maintainer">maintainer</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div>
                    <Button :disabled="!groupId || groupId === 'all' || !addEmail" @click="addMember">{{ $t('list.add') }}</Button>
                </div>
            </div>

            <!-- Members table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b">
                        <tr>
                            <th class="py-2 pr-2">Email</th>
                            <th class="py-2 pr-2">{{ $t('list.username') }}</th>
                            <th class="py-2 pr-2">{{ $t('list.joined_at') }}</th>
                            <th class="py-2 pr-2">{{ $t('list.role') }}</th>
                            <th class="py-2 pr-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="u in props.members?.data ?? []" :key="u.id" class="border-b">
                            <td class="py-2 pr-2">{{ u.email }}</td>
                            <td class="py-2 pr-2">{{ u.name }}</td>
                            <td class="py-2 pr-2">{{ u.joined_at }}</td>
                            <td class="py-2 pr-2">
                                <Select :model-value="u.role" @update:modelValue="(val) => changeRole(u, val as string)">
                                    <SelectTrigger class="w-[160px]">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="member">member</SelectItem>
                                        <SelectItem value="contributor">contributor</SelectItem>
                                        <SelectItem value="moderator">moderator</SelectItem>
                                        <SelectItem value="maintainer">maintainer</SelectItem>
                                    </SelectContent>
                                </Select>
                            </td>
                            <td class="py-2 pr-2 text-right">
                                <Button variant="destructive" @click="removeMember(u)">{{ $t('list.remove') }}</Button>
                            </td>
                        </tr>
                        <tr v-if="(props.members?.data ?? []).length === 0">
                            <td class="py-4 text-center text-muted-foreground" colspan="5">{{ $t('list.no_results') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination (basic) -->
            <div v-if="props.members?.links" class="flex flex-wrap items-center gap-2">
                <Button
                    v-for="l in props.members.links"
                    :key="l.url + String(l.label)"
                    :disabled="!l.url"
                    :variant="l.active ? 'default' : 'outline'"
                    @click="router.visit(l.url as any, { preserveState: true })"
                >
                    <template v-if="typeof l.label === 'string'">
                        <span v-if="l.label.toLowerCase().includes('previous')">{{ $t('pagination.previous') }}</span>
                        <span v-else-if="l.label.toLowerCase().includes('next')">{{ $t('pagination.next') }}</span>
                        <span v-else>{{ l.label }}</span>
                    </template>
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
