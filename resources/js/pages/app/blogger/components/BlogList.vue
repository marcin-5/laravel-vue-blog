<script lang="ts" setup>
import BlogListItem from '@/components/blogger/BlogListItem.vue';
import BlogEmptyState from './BlogEmptyState.vue';
import type { AdminBlog as Blog, Category } from '@/types/blog.types';

const props = defineProps<{
    blogs: Blog[];
    canCreate: boolean;
    categories: Category[];
}>();

const emit = defineEmits<{
    create: [];
}>();
</script>

<template>
    <div class="space-y-3">
        <BlogListItem v-for="blog in props.blogs" :key="blog.id" :categories="props.categories" :item="blog" />

        <BlogEmptyState v-if="props.blogs.length === 0" :can-create="props.canCreate" @create="emit('create')" />
    </div>
</template>
