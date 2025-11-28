<script lang="ts" setup>
import CategoryRow from '@/components/admin/CategoryRow.vue';
import { i18n } from '@/i18n';
import type { CategoryRow as CategoryRowType } from '@/types/admin.types';
import { computed } from 'vue';

const props = defineProps<{
    categories: CategoryRowType[];
    supportedLocales: readonly string[];
}>();

function localizedName(name: string | Record<string, string>): string {
    const locale = (i18n.global.locale.value as string) || 'en';
    if (typeof name === 'string') return name;
    return name?.[locale] ?? name?.en ?? Object.values(name ?? {})[0] ?? '';
}

const sortedCategories = computed(() => [...props.categories].sort((a, b) => localizedName(a.name).localeCompare(localizedName(b.name))));
</script>

<template>
    <div class="rounded-md border border-sidebar-border/70 p-4 dark:border-sidebar-border">
        <h2 class="mb-3 text-lg font-semibold">Categories</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-sidebar-border/70 text-xs text-muted-foreground uppercase dark:border-sidebar-border">
                    <tr>
                        <th class="py-2 pr-4">Name</th>
                        <th class="py-2 pr-4">Slug</th>
                        <th class="py-2 pr-4">Blogs</th>
                        <th class="py-2 pr-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <CategoryRow v-for="cat in sortedCategories" :key="cat.id" :category="cat" :supported-locales="props.supportedLocales" />
                </tbody>
            </table>
        </div>
    </div>
</template>
