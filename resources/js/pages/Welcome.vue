<script lang="ts" setup>
import BlogsGrid from '@/components/blog/BlogsGrid.vue';
import CategoriesFilter from '@/components/blog/CategoriesFilter.vue';
import NoBlogs from '@/components/blog/NoBlogs.vue';
import PublicNavbar from '@/components/PublicNavbar.vue';
import '@fontsource/cinzel-decorative/400.css';
import '@fontsource/cinzel-decorative/700.css';
import '@fontsource/cinzel-decorative/900.css';
import '@fontsource/cinzel/700.css';
import '@fontsource/cinzel/900.css';
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
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

const { t, tm } = useI18n();

const selected = computed<number[]>(() => props.selectedCategoryIds ?? []);

const slogans = tm('landing.slogans') as string[];
const randomSlogan = slogans[Math.floor(Math.random() * slogans.length)] || '';

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
            <div class="mb-12 text-center">
                <h1
                    class="inline-block p-2 text-5xl leading-none font-black tracking-tight text-slate-800 sm:text-8xl lg:text-9xl dark:text-slate-200"
                    style="font-family: 'Cinzel', serif"
                >
                    <span class="text-[1.0em] font-black" style="font-family: 'Cinzel Decorative', serif; font-weight: 900">O</span>sobliwy
                    <span class="text-[1.2em] font-black" style="font-family: 'Cinzel Decorative', serif; font-weight: 900">B</span>log
                </h1>
                <p class="mt-4 font-serif text-xl opacity-80 sm:text-2xl dark:text-white">— {{ randomSlogan }} —</p>
            </div>

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
            <BlogsGrid v-if="blogs.length > 0" :blogs="blogs" />
            <NoBlogs v-else />
        </div>
    </div>
</template>
