<script lang="ts" setup>
import { Card } from '@/components/ui/card';
import { getCategoryDisplayName } from '@/types/blog';
import type { BlogItem } from '@/types/blog.types';
import { handleContentClick } from '@/utils/domUtils';
import { hasContent } from '@/utils/stringUtils';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{ blog: BlogItem }>();

const { t } = useI18n();

// Computed properties
const blogUrl = computed(() => props.blog.url || `/${props.blog.slug}`);
const authorLabel = computed(() => t('blog.author', 'Author:'));
const hasAuthor = computed(() => hasContent(props.blog.author));
const hasDescription = computed(() => hasContent(props.blog.descriptionHtml));
const hasCategories = computed(() => props.blog.categories.length > 0);
</script>

<template>
    <Card class="border-stone-300 bg-olive-100 p-4 hover:shadow-md dark:border-neutral-700 dark:bg-mist-900">
        <h2 class="mb-1 font-header text-xl font-semibold text-mist-800 dark:text-mist-100">
            <a :href="blogUrl" class="hover:underline" @click="handleContentClick">
                {{ blog.name }}
            </a>
        </h2>

        <div v-if="hasAuthor" class="mb-2 font-footer text-sm text-mist-600 dark:text-mist-400">{{ authorLabel }} {{ blog.author }}</div>

        <div
            v-if="hasDescription"
            class="text-md mb-3 font-excerpt text-mist-700 dark:text-mist-300"
            data-nosnippet
            v-html="props.blog.descriptionHtml"
        />

        <div v-if="hasCategories" class="flex flex-wrap gap-2 font-nav">
            <span
                v-for="category in blog.categories"
                :key="category.id"
                class="rounded-full bg-olive-50 px-2 py-1 text-xs text-gray-700 dark:bg-zinc-700 dark:text-mist-200"
            >
                {{ getCategoryDisplayName(category) }}
            </span>
        </div>

        <slot />
    </Card>
</template>
