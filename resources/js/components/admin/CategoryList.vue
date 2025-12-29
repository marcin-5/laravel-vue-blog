<script lang="ts" setup>
import CategoryRow from '@/components/admin/CategoryRow.vue';
import { useI18nNs } from '@/composables/useI18nNs';
import { i18n } from '@/i18n';
import type { CategoryRow as CategoryRowType } from '@/types/admin.types';
import { computed } from 'vue';

const { t } = await useI18nNs('admin');

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
        <h2 class="mb-3 text-lg font-semibold">{{ t('admin.categories.title') }}</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-sidebar-border/70 text-xs text-muted-foreground uppercase dark:border-sidebar-border">
                    <tr>
                        <th class="py-2 pr-4">{{ t('admin.categories.table.name') }}</th>
                        <th class="py-2 pr-4">{{ t('admin.categories.table.slug') }}</th>
                        <th class="py-2 pr-4">{{ t('admin.categories.table.blogs') }}</th>
                        <th class="py-2 pr-4">{{ t('admin.categories.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <CategoryRow v-for="cat in sortedCategories" :key="cat.id" :category="cat" :supported-locales="props.supportedLocales" />
                </tbody>
            </table>
        </div>
    </div>
</template>
