<script lang="ts" setup>
import { useStatsPage } from '@/composables/useStatsPage';
import type { StatsPageProps } from '@/types/stats';
import BlogStats from './BlogStats.vue';
import PostStats from './PostStats.vue';
import SpecialAudienceStats from './SpecialAudienceStats.vue';
import AudienceStats from './AudienceStats.vue';

const props = defineProps<StatsPageProps>();
const {
    blogState,
    postState,
    visitorState,
    specialVisitorState,
    blogOptions,
    postBlogOptions,
    visitorBlogOptions,
    blogFilterLabel,
    postBlogFilterLabel,
    visitorBlogFilterLabel,
    specialVisitorBlogFilterLabel,
} = useStatsPage(props);
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
        <BlogStats
            v-model="blogState"
            :blog-filter-label="blogFilterLabel"
            :blog-options="blogOptions"
            :bloggers="props.options.bloggers"
            :data="props.data.blogs"
            :show-blogger-column="props.config.showBloggerColumn"
            :show-blogger-filter="props.config.showBloggerFilter"
        />

        <PostStats
            v-model="postState"
            :blog-filter-label="postBlogFilterLabel"
            :blog-options="postBlogOptions"
            :bloggers="props.options.bloggers"
            :data="props.data.posts"
            :show-blogger-filter="props.config.showBloggerFilter"
        />

        <AudienceStats
            v-model="visitorState"
            :blog-filter-label="visitorBlogFilterLabel"
            :blog-options="visitorBlogOptions"
            :data="props.data.visitorsFromPage ?? []"
        />

        <SpecialAudienceStats
            v-model="specialVisitorState"
            :blog-filter-label="specialVisitorBlogFilterLabel"
            :blog-options="visitorBlogOptions"
            :data="props.data.visitorsFromSpecial ?? []"
        />
    </div>
</template>
