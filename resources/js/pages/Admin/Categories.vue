<script lang="ts" setup>
import CategoryForm from '@/components/admin/CategoryForm.vue';
import CategoryList from '@/components/admin/CategoryList.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { CategoryRow } from '@/types/admin.types';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

const props = defineProps<{ categories: CategoryRow[] }>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Categories', href: '/admin/categories' }];

const supportedLocales = ['en', 'pl'] as const;
</script>

<template>
    <Head title="Categories" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
            <CategoryForm :supported-locales="supportedLocales" />
            <CategoryList :categories="props.categories" :supported-locales="supportedLocales" />
        </div>
    </AppLayout>
</template>
