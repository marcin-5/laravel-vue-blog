<script lang="ts" setup>
import RecommendedLinkCategoryForm from '@/components/blogger/RecommendedLinkCategoryForm.vue';
import RecommendedLinkCategoryList from '@/components/blogger/RecommendedLinkCategoryList.vue';
import { Button } from '@/components/ui/button';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

export interface RecommendedLinkCategory {
    id: number;
    name: string;
    display_order: number;
}

const props = defineProps<{
    blog: { id: number; name: string };
    categories: RecommendedLinkCategory[];
}>();

const { t } = useI18n();
const { toast } = useToast();
const editingCategory = ref<RecommendedLinkCategory | null>(null);
const categories = ref([...props.categories]);
const isReordering = ref(false);
const title = computed(() => t('blogger.recommended_link_categories.title', { blog: props.blog.name }));
const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('blogger.breadcrumb.dashboard'), href: '/dashboard' },
    { title: t('blogger.breadcrumb.index'), href: '/blogs' },
    { title: t('blogger.recommended_links.breadcrumb'), href: route('blogger.recommended-links.index', props.blog.id) },
    { title: t('blogger.recommended_link_categories.breadcrumb'), href: route('blogger.recommended-link-categories.index', props.blog.id) },
]);

watch(
    () => props.categories,
    (updatedCategories) => {
        categories.value = [...updatedCategories];
    },
);

function moveCategory(index: number, direction: -1 | 1) {
    const destination = index + direction;
    if (destination < 0 || destination >= categories.value.length || isReordering.value) {
        return;
    }

    const previousOrder = [...categories.value];
    const reorderedCategories = [...categories.value];
    const [category] = reorderedCategories.splice(index, 1);
    reorderedCategories.splice(destination, 0, category);
    categories.value = reorderedCategories;
    isReordering.value = true;

    router.put(
        route('blogger.recommended-link-categories.reorder', props.blog.id),
        { category_ids: reorderedCategories.map((item) => item.id) },
        {
            only: ['categories'],
            preserveScroll: true,
            onError: () => {
                categories.value = previousOrder;
                toast({
                    title: t('blogger.recommended_link_categories.reorder_failed'),
                    variant: 'destructive',
                });
            },
            onFinish: () => {
                isReordering.value = false;
            },
        },
    );
}

function editCategory(category: RecommendedLinkCategory) {
    editingCategory.value = category;
}

function deleteCategory(category: RecommendedLinkCategory) {
    if (!confirm(t('blogger.recommended_link_categories.confirm_delete', { name: category.name }))) {
        return;
    }

    router.delete(route('blogger.recommended-link-categories.destroy', [props.blog.id, category.id]), {
        preserveScroll: true,
        onSuccess: () => {
            if (editingCategory.value?.id === category.id) {
                editingCategory.value = null;
            }
        },
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="title" />
        <main class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4 sm:p-6">
            <header class="relative overflow-hidden rounded-xl border border-sidebar-border/70 bg-card px-5 py-6 shadow-sm sm:px-7">
                <div class="absolute inset-y-0 right-0 hidden w-1/3 bg-linear-to-l from-primary/5 to-transparent sm:block" />
                <div class="relative flex flex-wrap items-end justify-between gap-4">
                    <div class="space-y-2">
                        <p class="text-xs font-semibold tracking-[0.18em] text-muted-foreground uppercase">{{ props.blog.name }}</p>
                        <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">{{ title }}</h1>
                        <p class="max-w-xl text-sm leading-relaxed text-muted-foreground">{{ t('blogger.recommended_link_categories.description') }}</p>
                    </div>
                    <Button as-child variant="outline">
                        <Link :href="route('blogger.recommended-links.index', props.blog.id)">
                            <ArrowLeft class="mr-2 size-4" />
                            {{ t('blogger.recommended_link_categories.back_to_links') }}
                        </Link>
                    </Button>
                </div>
            </header>

            <RecommendedLinkCategoryForm
                :blog-id="props.blog.id"
                :category="editingCategory"
                @cancel="editingCategory = null"
                @saved="editingCategory = null"
            />

            <RecommendedLinkCategoryList
                :categories="categories"
                :processing="isReordering"
                @delete="deleteCategory"
                @edit="editCategory"
                @move="moveCategory"
            />
        </main>
    </AppLayout>
</template>
