<script lang="ts" setup>
import { Card } from '@/components/ui/card';
import { getCategoryDisplayName } from '@/types/blog';
import type { BlogItem } from '@/types/blog.types';
import { hasContent } from '@/utils/stringUtils';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{ blog: BlogItem }>();

const { t } = useI18n();

// Computed properties
const blogUrl = computed(() => `/${props.blog.slug}`);
const authorLabel = computed(() => t('blog.author', 'Author:'));
const hasAuthor = computed(() => hasContent(props.blog.author));
const hasDescription = computed(() => hasContent(props.blog.descriptionHtml));
const hasCategories = computed(() => props.blog.categories.length > 0);
</script>

<template>
    <Card class="border-gray-200 bg-olive-100 p-4 hover:shadow-md dark:border-gray-800 dark:bg-slate-900">
        <h2 class="mb-1 font-header text-xl font-semibold text-slate-800 dark:text-slate-100">
            <a :href="blogUrl" class="hover:underline">
                {{ blog.name }}
            </a>
        </h2>

        <div v-if="hasAuthor" class="mb-2 font-footer text-sm text-slate-600 dark:text-slate-400">{{ authorLabel }} {{ blog.author }}</div>

        <div
            v-if="hasDescription"
            class="mb-3 font-excerpt text-sm text-slate-600 dark:text-slate-300"
            data-nosnippet
            v-html="props.blog.descriptionHtml"
        />

        <div v-if="hasCategories" class="flex flex-wrap gap-2 font-nav">
            <span
                v-for="category in blog.categories"
                :key="category.id"
                class="rounded-full bg-olive-50 px-2 py-0.5 text-xs text-gray-700 dark:bg-slate-800 dark:text-slate-200"
            >
                {{ getCategoryDisplayName(category) }}
            </span>
        </div>

        <slot />
    </Card>
</template>
