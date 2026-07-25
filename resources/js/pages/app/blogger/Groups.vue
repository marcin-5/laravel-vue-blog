<script lang="ts" setup>
import GroupCreateSection from '@/pages/app/blogger/components/GroupCreateSection.vue';
import GroupList from '@/pages/app/blogger/components/GroupList.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { AdminGroup as Group } from '@/types/blog.types';
import { useTemplateRef } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{ groups: Group[]; canCreate: boolean }>();

const { t } = useI18n();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('blogger.breadcrumb.dashboard'), href: '/dashboard' },
    { title: t('blogger.breadcrumb.groups'), href: '/groups/content' },
];
const createSection = useTemplateRef<{ open: () => void }>('createSection');
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
            <GroupCreateSection ref="createSection" :can-create="props.canCreate" />
            <GroupList :can-create="props.canCreate" :groups="props.groups" @create="createSection?.open()" />
        </div>
    </AppLayout>
</template>
