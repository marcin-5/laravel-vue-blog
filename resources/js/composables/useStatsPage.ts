import { useStatsFilters } from '@/composables/useStatsFilters';
import { BLOG_SORT_OPTIONS, POST_SORT_OPTIONS, SPECIAL_VISITOR_SORT_OPTIONS, VISITOR_SORT_OPTIONS } from '@/constants/stats';
import type { StatsFilterConfig, StatsPageProps } from '@/types/stats';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

export function useStatsPage(props: Readonly<StatsPageProps>) {
    const { t } = useI18n();
    const serverFilters = {
        blog: props.filters.blog,
        post: props.filters.post,
        visitor: props.filters.visitor ?? props.filters.blog,
        specialVisitor: props.filters.specialVisitor ?? props.filters.visitor ?? props.filters.blog,
    };

    const filterStates = useStatsFilters(serverFilters, {
        routeName: props.config.routeName,
        storageKeyPrefix: props.config.routeName,
        showBloggerFilter: props.config.showBloggerFilter,
    });

    const getEffectiveBlogFilterLabel = (selectedBloggerId?: number | null): string => {
        if (props.config.blogFilterLabel) {
            return props.config.blogFilterLabel;
        }

        if (props.config.showBloggerFilter) {
            return selectedBloggerId ? t('stats.filters.all_my_blogs') : t('stats.filters.all_blogs');
        }

        return t('stats.filters.all_my_blogs');
    };

    const blogFilterLabel = computed(() => getEffectiveBlogFilterLabel(filterStates.blogState.value.blogger_id));
    const postBlogFilterLabel = computed(() => getEffectiveBlogFilterLabel(filterStates.postState.value.blogger_id));
    const visitorBlogFilterLabel = computed(() => getEffectiveBlogFilterLabel(filterStates.visitorState.value.blogger_id));
    const specialVisitorBlogFilterLabel = computed(() => getEffectiveBlogFilterLabel(filterStates.specialVisitorState.value.blogger_id));

    const blogOptions = computed(() => props.options.blogOptions);
    const postBlogOptions = computed(() => props.options.postBlogOptions ?? props.options.blogOptions);
    const visitorBlogOptions = computed(() => props.options.visitorBlogOptions ?? props.options.blogOptions);

    const blogFilterConfig = computed<StatsFilterConfig>(() => ({
        bloggers: props.options.bloggers,
        blogOptions: blogOptions.value,
        showBloggerFilter: props.config.showBloggerFilter,
        showBlogFilter: false,
        blogFilterLabel: blogFilterLabel.value,
        sortOptions: BLOG_SORT_OPTIONS,
    }));

    const postFilterConfig = computed<StatsFilterConfig>(() => ({
        bloggers: props.options.bloggers,
        blogOptions: postBlogOptions.value,
        showBloggerFilter: props.config.showBloggerFilter,
        blogFilterLabel: postBlogFilterLabel.value,
        sortOptions: POST_SORT_OPTIONS,
    }));

    const visitorFilterConfig = computed<StatsFilterConfig>(() => ({
        blogOptions: visitorBlogOptions.value,
        blogFilterLabel: visitorBlogFilterLabel.value,
        showBlogFilter: true,
        showGroupByFilter: true,
        sortOptions: VISITOR_SORT_OPTIONS,
    }));

    const specialVisitorFilterConfig = computed<StatsFilterConfig>(() => ({
        blogOptions: visitorBlogOptions.value,
        blogFilterLabel: specialVisitorBlogFilterLabel.value,
        showBlogFilter: true,
        showRangeFilter: false,
        showVisitorTypeFilter: true,
        sortOptions: SPECIAL_VISITOR_SORT_OPTIONS,
    }));

    return {
        ...filterStates,
        blogOptions,
        postBlogOptions,
        visitorBlogOptions,
        blogFilterLabel,
        postBlogFilterLabel,
        visitorBlogFilterLabel,
        specialVisitorBlogFilterLabel,
        blogFilterConfig,
        postFilterConfig,
        visitorFilterConfig,
        specialVisitorFilterConfig,
    };
}
