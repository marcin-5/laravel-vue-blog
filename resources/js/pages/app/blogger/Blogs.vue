<script lang="ts" setup>
import BlogCreateSection from '@/pages/app/blogger/components/BlogCreateSection.vue';
import BlogList from '@/pages/app/blogger/components/BlogList.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { AdminBlog as Blog, Category } from '@/types/blog.types';
import { useTemplateRef } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{ blogs: Blog[]; canCreate: boolean; categories: Category[] }>();
const { t } = useI18n();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('blogger.breadcrumb.dashboard'), href: '/dashboard' },
    { title: t('blogger.breadcrumb.index'), href: '/blogs' },
];
const createSection = useTemplateRef<{ open: () => void }>('createSection');
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
            <BlogCreateSection ref="createSection" :can-create="props.canCreate" :categories="props.categories" />
            <BlogList :blogs="props.blogs" :can-create="props.canCreate" :categories="props.categories" @create="createSection?.open()" />
        </div>
    </AppLayout>
</template>
