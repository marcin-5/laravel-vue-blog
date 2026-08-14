<script lang="ts" setup>
import AppLogo from '@/components/AppLogo.vue';
import BlogsGrid from '@/components/blog/BlogsGrid.vue';
import CategoriesFilter from '@/components/blog/CategoriesFilter.vue';
import NoBlogs from '@/components/blog/NoBlogs.vue';
import UserGroupsList from '@/components/blog/UserGroupsList.vue';
import { useWelcomeCategoryFilter } from '@/composables/useWelcomeCategoryFilter';
import { useWelcomeSlogan } from '@/composables/useWelcomeSlogan';
import PublicHomeLayout from '@/layouts/PublicHomeLayout.vue';
import type { BlogItem, CategoryItem } from '@/types/blog.types';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    blogs: BlogItem[];
    categories: CategoryItem[];
    selectedCategoryIds?: number[];
    locale?: string;
}>();

const selected = computed<number[]>(() => props.selectedCategoryIds ?? []);
const { t } = useI18n();
const { slogan } = useWelcomeSlogan();
const { toggleCategory: navigateCategory, clearFilter } = useWelcomeCategoryFilter();

function toggleCategory(categoryId: number): void {
    navigateCategory(selected.value, categoryId);
}
</script>

<template>
    <PublicHomeLayout>
        <div class="mb-12 text-center">
            <AppLogo />
            <p class="mt-4 font-slogan text-lg opacity-80 sm:text-xl md:text-2xl dark:text-white">— {{ slogan }} —</p>
        </div>

        <!-- Groups list -->
        <UserGroupsList />

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
    </PublicHomeLayout>
</template>
