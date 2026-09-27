<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps, BreadcrumbItem } from '@/types';
import type {
    PrivateConversation,
    PrivateConversationContextOption,
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
    contextOptions: PrivateConversationContextOption[];
}

const props = defineProps<Props>();
const page = usePage<AppPageProps>();
const { t } = useI18n();
const { sortBy, sortDir, perPage, contextId, changeContext, changeSortBy, changeSortDir, changePerPage, visitPage } = usePrivateMessages(
    props.filters,
);
const indexRoute = props.filters.context === 'blog' ? 'blog-private-conversations.index' : 'group-private-conversations.index';
const showRoute = props.filters.context === 'blog' ? 'blog-private-conversations.show' : 'group-private-conversations.show';
const breadcrumbs: BreadcrumbItem[] = [
    { title: t('blogger.breadcrumb.dashboard'), href: route('dashboard') },
    { title: t('messages.private_messaging.panel.title', 'Private messages'), href: route(indexRoute) },
];
</script>

<template>
    <Head :title="t('messages.private_messaging.panel.title', 'Private messages')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <h1 class="text-2xl font-semibold">{{ t('messages.private_messaging.panel.title', 'Private messages') }}</h1>
            <PrivateMessagesFilters
                :filters="{ context: props.filters.context, context_id: contextId, sort_by: sortBy, sort_dir: sortDir, per_page: Number(perPage) }"
                :context-options="props.contextOptions"
                @context-change="changeContext"
                @per-page-change="changePerPage"
                @sort-by-change="changeSortBy"
                @sort-dir-change="changeSortDir"
            />
            <div v-if="props.conversations.length === 0" class="rounded-lg border p-8 text-center text-muted-foreground">
                {{
                    t(
                        `messages.private_messaging.panel.empty_${props.filters.context}`,
                        props.filters.context === 'blog' ? 'You have no private blog conversations.' : 'You have no private group conversations.',
                    )
                }}
            </div>
            <div v-else class="grid gap-4 lg:grid-cols-[minmax(16rem,24rem)_1fr]">
                <PrivateConversationList
                    :conversations="props.conversations"
                    :selected-id="props.selectedConversation?.id"
                    @select="(conversation) => visitPage(route(showRoute, conversation.id))"
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
