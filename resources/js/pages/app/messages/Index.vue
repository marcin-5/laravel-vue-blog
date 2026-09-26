<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps, BreadcrumbItem } from '@/types';
import type {
    PrivateConversation,
    PrivateConversationFilters,
    PrivateConversationPagination as PrivateConversationPaginationData,
} from '@/types/private-messaging.types';
import { Head, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import PrivateConversationDetails from './components/PrivateConversationDetails.vue';
import PrivateConversationList from './components/PrivateConversationList.vue';
import PrivateConversationPagination from './components/PrivateConversationPagination.vue';
import PrivateMessagesFilters from './components/PrivateMessagesFilters.vue';
import { usePrivateMessages } from '@/composables/usePrivateMessages';

interface Props {
    conversations: PrivateConversation[];
    pagination: PrivateConversationPaginationData;
    selectedConversation: PrivateConversation | null;
    filters: PrivateConversationFilters;
}

const props = defineProps<Props>();
const page = usePage<AppPageProps>();
const { t } = useI18n();
const { sortBy, sortDir, perPage, changeSortBy, changeSortDir, changePerPage, visitPage } = usePrivateMessages(props.filters);
const breadcrumbs: BreadcrumbItem[] = [
    { title: t('blogger.breadcrumb.dashboard'), href: route('dashboard') },
    { title: t('messages.private_messaging.panel.title', 'Private messages'), href: route('private-conversations.index') },
];
</script>

<template>
    <Head :title="t('messages.private_messaging.panel.title', 'Private messages')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <h1 class="text-2xl font-semibold">{{ t('messages.private_messaging.panel.title', 'Private messages') }}</h1>
            <PrivateMessagesFilters
                :filters="{ sort_by: sortBy, sort_dir: sortDir, per_page: Number(perPage) }"
                @per-page-change="changePerPage"
                @sort-by-change="changeSortBy"
                @sort-dir-change="changeSortDir"
            />
            <div v-if="props.conversations.length === 0" class="rounded-lg border p-8 text-center text-muted-foreground">
                {{ t('messages.private_messaging.panel.empty', 'You have no private conversations.') }}
            </div>
            <div v-else class="grid gap-4 lg:grid-cols-[minmax(16rem,24rem)_1fr]">
                <PrivateConversationList
                    :conversations="props.conversations"
                    :selected-id="props.selectedConversation?.id"
                    @select="(conversation) => visitPage(route('private-conversations.show', conversation.id))"
                />
                <PrivateConversationDetails
                    v-if="props.selectedConversation"
                    :conversation="props.selectedConversation"
                    :user-id="page.props.auth.user?.id ?? 0"
                />
                <div v-else class="rounded-lg border p-8 text-center text-muted-foreground">
                    {{ t('messages.private_messaging.panel.select', 'Select a conversation to read its messages.') }}
                </div>
            </div>
            <PrivateConversationPagination :pagination="props.pagination" @visit-page="visitPage" />
        </div>
    </AppLayout>
</template>
