<script lang="ts" setup>
import { Card } from '@/components/ui/card';

interface CategoryItem { id: number; name: string | Record<string, string>; slug?: string }
interface BlogItem { id: number; name: string; slug: string; description?: string | null; categories: CategoryItem[] }

const props = defineProps<{ blog: BlogItem }>();
</script>

<template>
  <Card class="border-gray-200 bg-white p-4 hover:shadow-md dark:border-gray-800 dark:bg-slate-900">
    <h2 class="mb-1 text-xl font-semibold text-slate-800 dark:text-slate-100">
      <a :href="`/${props.blog.slug}`" class="hover:underline">{{ props.blog.name }}</a>
    </h2>
    <p v-if="props.blog.description" class="mb-3 text-sm text-slate-600 dark:text-slate-300">{{ props.blog.description }}</p>
    <div class="flex flex-wrap gap-2">
      <span
        v-for="cat in props.blog.categories"
        :key="cat.id"
        class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-700 dark:bg-slate-800 dark:text-slate-200"
      >
        {{ typeof cat.name === 'string' ? cat.name : '' }}
      </span>
    </div>
    <slot />
  </Card>
</template>
