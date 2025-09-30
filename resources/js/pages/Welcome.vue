<script lang="ts" setup>
import BlogsGrid from '@/components/blog/BlogsGrid.vue';
import CategoriesFilter from '@/components/blog/CategoriesFilter.vue';
import PublicNavbar from '@/components/PublicNavbar.vue';
import { ensureNamespace } from '@/i18n';
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted, onServerPrefetch } from 'vue';
import { useI18n } from 'vue-i18n';

interface Category {
    id: number;
    slug: string;
    name: string;
}

interface BlogItem {
    id: number;
    name: string;
    slug: string;
    author: string;
    descriptionHtml?: string | null;
    categories: Category[];
}

const props = defineProps<{
    blogs: BlogItem[];
    categories: Category[];
    selectedCategoryIds?: number[];
}>();

const composer = useI18n();
const { t, locale } = composer;

// Load namespace in SSR and on client without top-level await
const loadNs = async () => {
    try {
        await ensureNamespace(locale.value, 'landing', composer);
    } catch (error) {
        console.warn('Failed to load landing namespace:', error);
    }
};

onServerPrefetch(loadNs);
onMounted(loadNs);

const selected = computed<number[]>(() => props.selectedCategoryIds ?? []);

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
    <Head :title="t('landing.meta.welcomeTitle', 'Welcome')">
        <meta :content="t('landing.meta.welcomeDescription', 'Welcome to Laravel Blog')" name="description" />
    </Head>
    <div class="flex min-h-screen flex-col">
        <PublicNavbar />
        <div class="mx-auto w-full max-w-[1024px] p-6 lg:p-8">
            <h1 class="mb-4 text-4xl font-bold text-slate-800 dark:text-slate-200">Welcome!</h1>

            <!-- Categories Filter -->
            <CategoriesFilter
                :categories="categories"
                :clear-label="t('landing.actions.clear', 'Clear filter')"
                :selected-ids="selected"
                class="mb-6"
                @clear="clearFilter"
                @toggle="toggleCategory"
            />

            <!-- Blogs Grid -->
            <BlogsGrid :blogs="blogs" />
        </div>
    </div>
</template>
