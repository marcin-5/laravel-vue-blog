<script lang="ts" setup>
import BloggerManagementPanel from '@/components/blogger/BloggerManagementPanel.vue';
import BlogCreateSection from '@/pages/app/blogger/components/BlogCreateSection.vue';
import BlogList from '@/pages/app/blogger/components/BlogList.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { AdminBlog as Blog, Category } from '@/types/blog.types';
import { useI18n } from 'vue-i18n';

const props = defineProps<{ blogs: Blog[]; canCreate: boolean; categories: Category[] }>();
const { t } = useI18n();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('blogger.breadcrumb.dashboard'), href: '/dashboard' },
    { title: t('blogger.breadcrumb.index'), href: '/blogs' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <BloggerManagementPanel :can-create="props.canCreate" :items="props.blogs">
            <template #create="{ isOpen, open, close }">
                <BlogCreateSection :can-create="props.canCreate" :categories="props.categories" :close="close" :open="open" :show-create="isOpen" />
            </template>
            <template #list="{ items, canCreate, requestCreate }">
                <BlogList :blogs="items" :can-create="canCreate" :categories="props.categories" @create="requestCreate" />
            </template>
        </BloggerManagementPanel>
    </AppLayout>
</template>
