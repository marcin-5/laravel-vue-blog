<script lang="ts" setup>
import AppLogo from '@/components/AppLogo.vue';
import BlogsGrid from '@/components/blog/BlogsGrid.vue';
import CategoriesFilter from '@/components/blog/CategoriesFilter.vue';
import NoBlogs from '@/components/blog/NoBlogs.vue';
import PublicNavbar from '@/components/PublicNavbar.vue';
import type { BlogItem, CategoryItem } from '@/types/blog.types';
import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    blogs: BlogItem[];
    categories: CategoryItem[];
    selectedCategoryIds?: number[];
    locale?: string;
}>();

const { t, tm } = useI18n();
const page = usePage();

const selected = computed<number[]>(() => props.selectedCategoryIds ?? []);

// Make motto selection reactive to page URL to ensure it recalculates on navigation
const randomSlogan = computed(() => {
    // Use current URL as dependency to trigger re-computation on navigation
    const currentUrl = page.url;
    const slogans = tm('slogans') as string[];

    // Create a seed from the URL to ensure deterministic yet varied selection per page visit
    // Using URL length and random to pick different motto on each navigation
    const urlSeed = currentUrl.length + Math.random();
    return slogans[Math.floor(urlSeed * slogans.length) % slogans.length] || '';
});

function toggleCategory(id: number) {
    const set = new Set(selected.value);
    if (set.has(id)) set.delete(id);
    else set.add(id);
    const ids = Array.from(set.values());

    const query: Record<string, any> = {};
    if (ids.length > 0) {
        query.categories = ids.join(',');
    }

    router.get('/', query, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
}

function clearFilter() {
    router.get('/', {}, { preserveScroll: true, preserveState: true, replace: true });
}
</script>

<template>
    <div class="flex min-h-screen flex-col">
        <PublicNavbar />
        <div class="mx-auto w-full max-w-[1024px] p-6 lg:p-8">
            <div class="mb-12 text-center">
                <AppLogo />
                <p class="mt-4 font-serif text-lg opacity-80 sm:text-xl md:text-2xl dark:text-white">— {{ randomSlogan }} —</p>
            </div>

            <!-- Categories Filter -->
            <CategoriesFilter
                :categories="categories"
                :clear-label="t('actions.clear', 'Clear filter')"
                :selected-ids="selected"
                class="mb-6"
                @clear="clearFilter"
                @toggle="toggleCategory"
            />

            <!-- Blogs Grid -->
            <BlogsGrid v-if="blogs.length > 0" :blogs="blogs" />
            <NoBlogs v-else />
        </div>
    </div>
</template>
