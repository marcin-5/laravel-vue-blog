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
    blogFilterConfig,
    postFilterConfig,
    visitorFilterConfig,
    specialVisitorFilterConfig,
} = useStatsPage(props);
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
        <BlogStats
            v-model="blogState"
            :filter-config="blogFilterConfig"
            :data="props.data.blogs"
            :show-blogger-column="props.config.showBloggerColumn"
        />

        <PostStats v-model="postState" :filter-config="postFilterConfig" :data="props.data.posts" />

        <AudienceStats v-model="visitorState" :filter-config="visitorFilterConfig" :data="props.data.visitorsFromPage ?? []" />

        <SpecialAudienceStats
            v-model="specialVisitorState"
            :filter-config="specialVisitorFilterConfig"
            :data="props.data.visitorsFromSpecial ?? []"
        />
    </div>
</template>
