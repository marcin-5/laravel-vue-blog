<script lang="ts" setup>
import type { BlogItem } from '@/types/blog';
import { computed } from 'vue';
import BlogCard from './BlogCard.vue';

const props = defineProps<{
    blogs: BlogItem[];
    class?: string;
}>();

// CSS Classes
const GRID_BASE_CLASSES = 'grid grid-cols-1 gap-4 sm:grid-cols-2';

// Computed properties
const gridClasses = computed(() => [GRID_BASE_CLASSES, props.class]);
const hasBlogs = computed(() => props.blogs.length > 0);
</script>

<template>
    <div v-if="hasBlogs" :class="gridClasses">
        <BlogCard v-for="blog in blogs" :key="blog.id" :blog="blog" />
    </div>
</template>
