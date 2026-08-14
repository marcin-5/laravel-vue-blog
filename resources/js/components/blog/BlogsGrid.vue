<script lang="ts" setup>
import type { BlogItem } from '@/types/blog.types';
import { computed } from 'vue';
import BlogCard from './BlogCard.vue';

const props = defineProps<{
    blogs: BlogItem[];
    class?: string;
}>();

const GRID_BASE_CLASSES = 'grid grid-cols-1 gap-4 sm:grid-cols-2';

const gridClasses = computed(() => [GRID_BASE_CLASSES, props.class]);
</script>

<template>
    <div :class="gridClasses">
        <BlogCard v-for="blog in blogs" :key="blog.id" :blog="blog" />
    </div>
</template>
