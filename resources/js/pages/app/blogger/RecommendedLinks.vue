<script lang="ts" setup>
import InputError from '@/components/InputError.vue';
import RecommendedLinkSections from '@/components/blogger/RecommendedLinkSections.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

interface RecommendedLinkCategory {
    id: number;
    name: string;
    display_order: number;
}

interface RecommendedLink {
    id: number;
    url: string;
    name: string | null;
    description: string | null;
    category_id: number | null;
    display_order: number;
}

const props = defineProps<{
    blog: { id: number; name: string };
    categories: RecommendedLinkCategory[];
    recommendedLinks: RecommendedLink[];
}>();

const { t } = useI18n();
const title = computed(() => t('blogger.recommended_links.title', { blog: props.blog.name }));
const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('blogger.breadcrumb.dashboard'), href: '/dashboard' },
    { title: t('blogger.breadcrumb.index'), href: '/blogs' },
    { title: title.value, href: route('blogger.recommended-links.index', props.blog.id) },
]);

const createForm = useForm({ url: '', name: '', description: '', category_id: null as number | null });

function submitCreate() {
    createForm.post(route('blogger.recommended-links.store', props.blog.id), {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="title" />
        <div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-xl font-semibold">{{ title }}</h1>
                <Link
                    :href="route('blogger.recommended-link-categories.index', props.blog.id)"
                    class="inline-flex items-center rounded-md border px-3 py-2 text-sm font-medium transition hover:bg-muted"
                >
                    {{ t('blogger.recommended_links.manage_categories') }}
                </Link>
            </div>

            <form class="space-y-4 rounded-xl border border-sidebar-border/70 bg-card p-5 shadow-sm" @submit.prevent="submitCreate">
                <div>
                    <label class="mb-1 block text-sm font-medium" for="recommended-link-url">{{ t('blogger.recommended_links.url') }}</label>
                    <input id="recommended-link-url" v-model="createForm.url" class="w-full rounded-md border px-3 py-2" required type="url" />
                    <InputError :message="createForm.errors.url" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium" for="recommended-link-name">{{ t('blogger.recommended_links.name') }}</label>
                    <input id="recommended-link-name" v-model="createForm.name" class="w-full rounded-md border px-3 py-2" type="text" />
                    <InputError :message="createForm.errors.name" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium" for="recommended-link-description">{{
                        t('blogger.recommended_links.description')
                    }}</label>
                    <textarea
                        id="recommended-link-description"
                        v-model="createForm.description"
                        class="w-full rounded-md border px-3 py-2"
                        rows="3"
                    />
                    <InputError :message="createForm.errors.description" />
                </div>
                <div class="order-first flex-1 space-y-1.5 sm:order-0">
                    <label class="text-sm font-medium" for="recommended-link-category">{{ t('blogger.recommended_links.category') }}</label>
                    <select
                        id="recommended-link-category"
                        v-model="createForm.category_id"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option :value="null">{{ t('blogger.recommended_links.uncategorized') }}</option>
                        <option v-for="category in props.categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                    </select>
                    <InputError :message="createForm.errors.category_id" />
                </div>
                <Button :disabled="createForm.processing" type="submit" variant="constructive">
                    {{ t('blogger.recommended_links.add') }}
                </Button>
            </form>

            <RecommendedLinkSections :blog-id="props.blog.id" :categories="props.categories" :recommended-links="props.recommendedLinks" />
        </div>
    </AppLayout>
</template>
