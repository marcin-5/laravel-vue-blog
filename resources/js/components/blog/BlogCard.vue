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

// CSS Classes
const CARD_CLASSES = 'border-gray-200 bg-white p-4 hover:shadow-md dark:border-gray-800 dark:bg-slate-900';
const TITLE_CLASSES = 'mb-1 font-header text-xl font-semibold text-slate-800 dark:text-slate-100';
const LINK_CLASSES = 'hover:underline';
const AUTHOR_CLASSES = 'mb-2 font-footer text-sm text-slate-600 dark:text-slate-400';
const DESCRIPTION_CLASSES = 'mb-3 text-sm text-slate-600 dark:text-slate-300';
const CATEGORY_CONTAINER_CLASSES = 'flex flex-wrap gap-2 font-nav';
const CATEGORY_BADGE_CLASSES = 'rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-700 dark:bg-slate-800 dark:text-slate-200';
</script>

<template>
    <Card :class="CARD_CLASSES">
        <h2 :class="TITLE_CLASSES">
            <a :class="LINK_CLASSES" :href="blogUrl">
                {{ blog.name }}
            </a>
        </h2>

        <div v-if="hasAuthor" :class="AUTHOR_CLASSES">{{ authorLabel }} {{ blog.author }}</div>

        <div v-if="hasDescription" :class="DESCRIPTION_CLASSES" data-nosnippet v-html="props.blog.descriptionHtml" />

        <div v-if="hasCategories" :class="CATEGORY_CONTAINER_CLASSES">
            <span v-for="category in blog.categories" :key="category.id" :class="CATEGORY_BADGE_CLASSES">
                {{ getCategoryDisplayName(category) }}
            </span>
        </div>

        <slot />
    </Card>
</template>
