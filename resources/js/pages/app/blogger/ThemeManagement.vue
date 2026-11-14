<script lang="ts" setup>
import ThemeManagementForm from '@/components/blogger/ThemeManagementForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import type { BreadcrumbItem } from '@/types';
import type { ThemeManagementEntity } from '@/types/blog.types';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    entityType: 'blog' | 'group';
    entities: ThemeManagementEntity[];
}>();

const { t } = useI18n();
const title = computed(() => t(props.entityType === 'blog' ? 'blogger.theme_management.blog_title' : 'blogger.theme_management.group_title'));
const sectionTitle = computed(() => t(props.entityType === 'blog' ? 'blogger.breadcrumb.index' : 'blogger.breadcrumb.groups'));
const sectionHref = computed(() => (props.entityType === 'blog' ? '/blogs' : '/groups/content'));
const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('blogger.breadcrumb.dashboard'), href: '/dashboard' },
    { title: sectionTitle.value, href: sectionHref.value },
    { title: title.value, href: props.entityType === 'blog' ? route('blogger.blogs.theme.index') : route('blogger.groups.theme.index') },
]);
</script>

<template>
    <Head :title="title" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <ThemeManagementForm :entities="props.entities" :entity-type="props.entityType" />
    </AppLayout>
</template>