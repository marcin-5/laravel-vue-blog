<script lang="ts" setup>
import AppLogo from '@/components/AppLogo.vue';
import BlogsGrid from '@/components/blog/BlogsGrid.vue';
import CategoriesFilter from '@/components/blog/CategoriesFilter.vue';
import NoBlogs from '@/components/blog/NoBlogs.vue';
import PublicNavbar from '@/components/PublicNavbar.vue';
import SeoHead from '@/components/seo/SeoHead.vue';
import { router } from '@inertiajs/vue3';
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
    locale?: string;
}>();

const { t, tm } = useI18n();

const selected = computed<number[]>(() => props.selectedCategoryIds ?? []);

const slogans = tm('landing.slogans') as string[];
const randomSlogan = slogans[Math.floor(Math.random() * slogans.length)] || '';

// SEO helpers - SSR compatible
const baseUrl = import.meta.env.VITE_APP_URL || 'https://osobliwy.blog';
const canonicalUrl = computed(() => {
    const categoryParam = selected.value.length > 0 ? `?categories=${selected.value.join(',')}` : '';
    return `${baseUrl}${categoryParam}`;
});
const seoTitle = computed(() => t('landing.meta.welcomeTitle', 'Welcome'));
const seoDescription = computed(() => t('landing.meta.welcomeDescription', 'Welcome to Osobliwy Blog'));
const seoImage = computed(() => `${baseUrl}/og-image.png`);

// Structured data for SEO
const structuredData = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'Blog',
    name: seoTitle.value,
    url: baseUrl,
    description: seoDescription.value,
    blogPost: props.blogs.slice(0, 10).map((blog) => ({
        '@type': 'BlogPosting',
        headline: blog.name,
        author: {
            '@type': 'Person',
            name: blog.author,
        },
        url: `${baseUrl}/blogs/${blog.slug}`,
        description: blog.descriptionHtml?.replace(/<[^>]*>/g, '').substring(0, 700),
    })),
}));

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
    <SeoHead
        :canonical-url="canonicalUrl"
        :description="seoDescription"
        :locale="locale"
        :og-image="seoImage"
        :structured-data="structuredData"
        :title="seoTitle"
        og-type="website"
    />
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
