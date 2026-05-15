<script lang="ts" setup>
import StatsSkeleton from '@/components/StatsSkeleton.vue';
import type { BlogStats, PostsStats } from '@/types/stats';
import PlaceholderPattern from '../PlaceholderPattern.vue';
import BloggerStats from './BloggerStats.vue';
import PostPerformanceStats from './PostPerformanceStats.vue';
import PostTimelineStats from './PostTimelineStats.vue';

defineProps<{
    blogStats?: BlogStats[];
    postsStats?: PostsStats;
}>();
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <PostTimelineStats v-if="postsStats" :posts="postsStats.timeline" class="aspect-video" />
            <StatsSkeleton v-else class="aspect-video" />

            <PostPerformanceStats v-if="postsStats" :posts="postsStats.performance" class="aspect-video" />
            <StatsSkeleton v-else class="aspect-video" />

            <StatsSkeleton class="aspect-video" />
        </div>
        <BloggerStats v-if="blogStats" :stats="blogStats" />
        <StatsSkeleton v-else class="h-48" />
        <div class="relative min-h-screen flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
            <PlaceholderPattern />
        </div>
    </div>
</template>
