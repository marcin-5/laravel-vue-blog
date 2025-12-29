<script lang="ts" setup>
import type { BlogStats, PostsStats } from '@/types/stats';
import PlaceholderPattern from '../PlaceholderPattern.vue';
import BloggerStats from './BloggerStats.vue';
import PostPerformanceStats from './PostPerformanceStats.vue';
import PostTimelineStats from './PostTimelineStats.vue';

defineProps<{
    blogStats: BlogStats[];
    postsStats?: PostsStats;
}>();
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <PostTimelineStats :posts="postsStats?.timeline || []" />
            </div>
            <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <PostPerformanceStats :posts="postsStats?.performance || []" />
            </div>
            <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <PlaceholderPattern />
            </div>
        </div>
        <BloggerStats :stats="blogStats" />
        <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
            <PlaceholderPattern />
        </div>
    </div>
</template>
