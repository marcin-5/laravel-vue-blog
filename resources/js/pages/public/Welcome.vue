<script lang="ts" setup>
import AppLogo from '@/components/AppLogo.vue';
import BlogsGrid from '@/components/blog/BlogsGrid.vue';
import CategoriesFilter from '@/components/blog/CategoriesFilter.vue';
import NoBlogs from '@/components/blog/NoBlogs.vue';
import UserGroupsList from '@/components/blog/UserGroupsList.vue';
import { useWelcomeCategoryFilter } from '@/composables/useWelcomeCategoryFilter';
import PublicHomeLayout from '@/layouts/PublicHomeLayout.vue';
import type { BlogItem, CategoryItem } from '@/types/blog.types';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    blogs: BlogItem[];
    categories: CategoryItem[];
    selectedCategoryIds?: number[];
    locale?: string;
    displayedSlogan?: string | null;
}>();

const selected = computed<number[]>(() => props.selectedCategoryIds ?? []);
const { t } = useI18n();
const { toggleCategory: navigateCategory, clearFilter } = useWelcomeCategoryFilter();

function toggleCategory(categoryId: number): void {
    navigateCategory(selected.value, categoryId);
}
</script>

<template>
    <PublicHomeLayout>
        <div class="flex flex-1 flex-col">
            <div class="flex-1">
                <div class="mb-12 text-center">
                    <AppLogo />
                    <p v-if="displayedSlogan" class="mt-4 font-slogan text-lg opacity-80 sm:text-xl md:text-2xl dark:text-white">
                        — {{ displayedSlogan }} —
                    </p>
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
                <div v-if="blogs.length > 0">
                    <BlogsGrid :blogs="blogs" />
                    <p class="sm:text-md mt-12 text-center font-body text-sm opacity-80 md:text-lg"></p>
                </div>

                <NoBlogs v-else />
            </div>

            <footer class="mt-10 flex items-center justify-center gap-4 border-t border-border/60 py-3 font-footer text-xs text-muted-foreground">
                <Link :href="route('about')" class="transition-colors hover:text-foreground">{{ t('common.nav.about_home') }}</Link>
                <span aria-hidden="true">·</span>
                <Link :href="route('contact')" class="transition-colors hover:text-foreground">{{ t('common.nav.contact') }}</Link>
            </footer>
        </div>
    </PublicHomeLayout>
</template>
