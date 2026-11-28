<script lang="ts" setup>
import AppLayout from '@/layouts/AppLayout.vue';
import IndexNowFailures from '@/components/admin/IndexNowFailures.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

interface Submission {
    id: number;
    url: string;
    blog_id: number | null;
    post_id: number | null;
    succeeded: boolean;
    http_status: number | null;
    error_message: string | null;
    submitted_at: string;
    blog: { id: number; name: string } | null;
    post: { id: number; title: string } | null;
}

interface PaginatedSubmissions {
    data: Submission[];
    current_page: number;
    last_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

interface LogType {
    value: string;
    label: string;
}

const props = defineProps<{
    logs: PaginatedSubmissions;
    type: string;
    perPage: number;
    perPageOptions: number[];
    logTypes: LogType[];
    indexNowFailureCount: number;
}>();

const { t } = useI18n();
const breadcrumbs: BreadcrumbItem[] = [{ title: t('admin.logs.title'), href: route('admin.logs.index') }];

function updateFilters(filters: Record<string, string | number>): void {
    router.get(route('admin.logs.index'), filters, { preserveState: true, preserveScroll: true, replace: true });
}
</script>

<template>
    <Head :title="t('admin.logs.title')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="flex flex-wrap items-end gap-4">
                <label class="flex flex-col gap-2 text-sm font-medium">
                    {{ t('admin.logs.type') }}
                    <select
                        :value="props.type"
                        class="rounded-md border bg-background px-3 py-2"
                        @change="updateFilters({ type: ($event.target as HTMLSelectElement).value, per_page: props.perPage })"
                    >
                        <option v-for="logType in props.logTypes" :key="logType.value" :value="logType.value">
                            {{ t(`admin.logs.${logType.value}`) }}
                        </option>
                    </select>
                </label>
                <label class="flex flex-col gap-2 text-sm font-medium">
                    {{ t('list.per_page') }}
                    <select
                        :value="props.perPage"
                        class="rounded-md border bg-background px-3 py-2"
                        @change="updateFilters({ type: props.type, per_page: Number(($event.target as HTMLSelectElement).value) })"
                    >
                        <option v-for="pageSize in props.perPageOptions" :key="pageSize" :value="pageSize">{{ pageSize }}</option>
                    </select>
                </label>
            </div>

            <IndexNowFailures :failure-count="props.indexNowFailureCount" />

            <div class="overflow-x-auto rounded-lg border">
                <table class="w-full min-w-225 text-left text-sm">
                    <thead class="bg-muted/50">
                        <tr>
                            <th class="p-3">{{ t('admin.logs.submitted_at') }}</th>
                            <th class="p-3">{{ t('admin.logs.result') }}</th>
                            <th class="p-3">{{ t('admin.logs.blog') }}</th>
                            <th class="p-3">{{ t('admin.logs.post') }}</th>
                            <th class="p-3">{{ t('admin.logs.url') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="submission in props.logs.data" :key="submission.id" class="border-t align-top">
                            <td class="p-3 whitespace-nowrap">{{ new Date(submission.submitted_at).toLocaleString() }}</td>
                            <td class="p-3">
                                <span :class="submission.succeeded ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400'">
                                    {{ submission.succeeded ? t('admin.logs.success') : t('admin.logs.failure') }}
                                </span>
                                <span v-if="submission.http_status" class="block text-xs text-muted-foreground"
                                    >HTTP {{ submission.http_status }}</span
                                >
                            </td>
                            <td class="p-3">
                                {{ submission.blog?.name ?? (submission.blog_id ? t('admin.logs.deleted_blog', { id: submission.blog_id }) : '—') }}
                            </td>
                            <td class="p-3">
                                {{ submission.post?.title ?? (submission.post_id ? t('admin.logs.deleted_post', { id: submission.post_id }) : '—') }}
                            </td>
                            <td class="max-w-xl p-3 font-mono text-xs break-all">{{ submission.url }}</td>
                        </tr>
                        <tr v-if="props.logs.data.length === 0">
                            <td class="p-8 text-center text-muted-foreground" colspan="5">{{ t('admin.logs.no_data') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between gap-4 text-sm">
                <span>{{ props.logs.total }} · {{ props.logs.current_page }} / {{ props.logs.last_page }}</span>
                <div class="flex gap-2">
                    <Link v-if="props.logs.prev_page_url" :href="props.logs.prev_page_url" class="rounded-md border px-3 py-2">{{
                        t('pagination.previous')
                    }}</Link>
                    <Link v-if="props.logs.next_page_url" :href="props.logs.next_page_url" class="rounded-md border px-3 py-2">{{
                        t('pagination.next')
                    }}</Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
