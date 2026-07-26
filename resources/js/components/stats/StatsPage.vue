<script lang="ts" setup>
import { useStatsFilters } from '@/composables/useStatsFilters';
import type { BlogOption, BlogRow, FilterState, PostRow, UserOption, VisitorRow } from '@/types/stats';
import { useI18n } from 'vue-i18n';
import BlogStats from './BlogStats.vue';
import PostStats from './PostStats.vue';
import SpecialAudienceStats from './SpecialAudienceStats.vue';
import AudienceStats from './AudienceStats.vue';

const { t } = useI18n();

export type StatsPageProps = {
    filters: {
        blog: FilterState;
        post: FilterState;
        visitor?: FilterState;
        specialVisitor?: FilterState;
    };
    data: {
        blogs: BlogRow[];
        posts: PostRow[];
        visitorsFromPage?: VisitorRow[];
        visitorsFromSpecial?: VisitorRow[];
    };
    options: {
        bloggers?: UserOption[];
        blogOptions: BlogOption[];
        postBlogOptions?: BlogOption[];
        visitorBlogOptions?: BlogOption[];
    };
    config: {
        routeName: string;
        showBloggerFilter?: boolean;
        showBloggerColumn?: boolean;
        blogFilterLabel?: string;
    };
};

const props = defineProps<StatsPageProps>();

const getEffectiveBlogFilterLabel = (selectedBloggerId?: number | null) => {
    if (props.config.blogFilterLabel) {
        return props.config.blogFilterLabel;
    }

    if (props.config.showBloggerFilter) {
        return selectedBloggerId ? t('stats.filters.all_my_blogs') : t('stats.filters.all_blogs');
    }

    return t('stats.filters.all_my_blogs');
};

const createInitialFilters = () => {
    return {
        blog: props.filters.blog,
        post: props.filters.post,
        visitor: props.filters.visitor ?? props.filters.blog,
        specialVisitor: props.filters.specialVisitor ?? props.filters.visitor ?? props.filters.blog,
    };
};

const { blogState, postState, visitorState, specialVisitorState } = useStatsFilters(createInitialFilters(), {
    routeName: props.config.routeName,
    storageKeyPrefix: props.config.routeName,
    showBloggerFilter: props.config.showBloggerFilter,
});
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
        <BlogStats
            v-model="blogState"
            :blog-filter-label="getEffectiveBlogFilterLabel(blogState.blogger_id)"
            :blog-options="props.options.blogOptions"
            :bloggers="props.options.bloggers"
            :data="props.data.blogs"
            :show-blogger-column="props.config.showBloggerColumn"
            :show-blogger-filter="props.config.showBloggerFilter"
        />

        <PostStats
            v-model="postState"
            :blog-filter-label="getEffectiveBlogFilterLabel(postState.blogger_id)"
            :blog-options="props.options.postBlogOptions ?? props.options.blogOptions"
            :bloggers="props.options.bloggers"
            :data="props.data.posts"
            :show-blogger-filter="props.config.showBloggerFilter"
        />

        <AudienceStats
            v-model="visitorState"
            :blog-filter-label="getEffectiveBlogFilterLabel(visitorState.blogger_id)"
            :blog-options="props.options.visitorBlogOptions ?? props.options.blogOptions"
            :data="props.data.visitorsFromPage ?? []"
        />

        <SpecialAudienceStats
            v-model="specialVisitorState"
            :blog-filter-label="getEffectiveBlogFilterLabel(specialVisitorState.blogger_id)"
            :blog-options="props.options.visitorBlogOptions ?? props.options.blogOptions"
            :data="props.data.visitorsFromSpecial ?? []"
        />
    </div>
</template>
