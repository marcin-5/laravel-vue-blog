<script lang="ts" setup>
import BloggerManagementPanel from '@/components/blogger/BloggerManagementPanel.vue';
import GroupCreateSection from '@/pages/app/blogger/components/GroupCreateSection.vue';
import GroupList from '@/pages/app/blogger/components/GroupList.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { AdminGroup as Group } from '@/types/blog.types';
import { useI18n } from 'vue-i18n';

const props = defineProps<{ groups: Group[]; canCreate: boolean }>();

const { t } = useI18n();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('blogger.breadcrumb.dashboard'), href: '/dashboard' },
    { title: t('blogger.breadcrumb.groups'), href: '/groups/content' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <BloggerManagementPanel :can-create="props.canCreate" :items="props.groups">
            <template #create="{ isOpen, open, close }">
                <GroupCreateSection :can-create="props.canCreate" :close="close" :open="open" :show-create="isOpen" />
            </template>
            <template #list="{ items, canCreate, requestCreate }">
                <GroupList :can-create="canCreate" :groups="items" @create="requestCreate" />
            </template>
        </BloggerManagementPanel>
    </AppLayout>
</template>
