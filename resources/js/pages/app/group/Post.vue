<script lang="ts" setup>
import BlogLayout from '@/components/blog/BlogLayout.vue';
import BlogPostNav from '@/components/blog/BlogPostNav.vue';
import BlogPostsList from '@/components/blog/BlogPostsList.vue';
import BorderDivider from '@/components/blog/BorderDivider.vue';
import PostContent from '@/components/blog/PostContent.vue';
import PostExtensions from '@/components/blog/PostExtensions.vue';
import PostHeader from '@/components/blog/PostHeader.vue';
import type { Navigation, Pagination, PostDetails, PostItem } from '@/types/blog.types';
import { Head } from '@inertiajs/vue3';

defineProps<{
    group: {
        id: number;
        name: string;
        slug: string;
    };
    post: PostDetails;
    posts: PostItem[];
    sidebar?: number; // numeric sidebar value (-50..50). Sign indicates side: negative -> left, positive -> right, 0 -> no sidebar
    pagination?: Pagination | null;
    navigation?: Navigation;
    theme?: any;
}>();
</script>

<template>
    <Head :title="post.title + ' - ' + group.name" />

    <BlogLayout :isPublic="false" :sidebar="sidebar" :theme="theme" maxWidthClass="max-w-5xl xl:max-w-7xl 2xl:max-w-screen-2xl">
        <template #top-divider>
            <BorderDivider class="mb-4" />
        </template>

        <template #header>
            <PostHeader :post="post" />
            <BorderDivider v-if="!sidebar" class="mb-8" />
        </template>

        <template #content>
            <PostContent :author="post.author" :content="post.contentHtml" />
            <PostExtensions :extensions="post.extensions || []" />
            <PostContent v-if="post.summaryHtml" :author="post.author" :content="post.summaryHtml" class="mt-8" />
        </template>

        <template #middle-divider>
            <BorderDivider class="mt-12 mb-4" />
        </template>

        <template #sidebar-content>
            <BlogPostsList :blogId="group.id" :blogSlug="group.slug" :class="{ 'mt-6': !sidebar }" :pagination="pagination" :posts="posts" is-group />
        </template>

        <template #navigation>
            <BlogPostNav :navigation="navigation" />
        </template>
    </BlogLayout>
</template>
