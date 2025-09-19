<script lang="ts" setup>
import BlogCard from './BlogCard.vue';

interface CategoryItem { id: number; name: string | Record<string, string>; slug?: string }
interface BlogItem { id: number; name: string; slug: string; description?: string | null; categories: CategoryItem[] }

const props = defineProps<{ blogs: BlogItem[]; class?: string }>();
</script>

<template>
  <div :class="['grid grid-cols-1 gap-4 sm:grid-cols-2', props.class]">
    <BlogCard v-for="blog in props.blogs" :key="blog.id" :blog="blog" />
  </div>
</template>
