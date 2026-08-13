export type StatsRange = 'today' | 'week' | 'month' | 'half_year' | 'year' | 'lifetime';

export type StatsGroupBy = 'visitor_id' | 'fingerprint';
export type StatsVisitorType = 'all' | 'bots' | 'anonymous' | 'markdown';

export interface StatsSortOption {
    readonly value: string;
    readonly label: string;
}

export interface StatsTableColumn {
    key: string;
    label: string;
    visible?: boolean;
    hasInfo?: boolean;
    isDate?: boolean;
}

export type BlogRow = {
    blog_id: number;
    name: string;
    owner_id: number;
    owner_name: string;
    views: number;
    unique_views: number;
    post_views: number;
    unique_post_views: number;
    markdown_views: number;
};

export type PostRow = {
    post_id: number;
    title: string;
    views: number;
    unique_views: number;
    bot_views: number;
    anonymous_views: number;
    markdown_views: number;
};

export type VisitorRow = {
    visitor_label: string;
    blog_views: number;
    post_views: number;
    views: number;
    lifetime_views: number;
    user_agent?: string | null;
};

export type UserOption = { id: number; name: string };
export type BlogOption = { id: number; name: string };

export interface FilterState {
    range: StatsRange;
    sort: string;
    size: number;
    blogger_id?: number | null;
    blog_id?: number | null;
    group_by?: StatsGroupBy;
    visitor_type?: StatsVisitorType;
}

export interface StatsQuery extends Record<string, string | number | undefined> {
    range: StatsRange;
    sort: string;
    size: number;
    blogger_id?: number;
    blog_id?: number;
    posts_range: StatsRange;
    posts_sort: string;
    posts_size: number;
    posts_blogger_id?: number;
    posts_blog_id?: number;
    visitors_range: StatsRange;
    visitors_sort: string;
    visitors_size: number;
    visitors_group_by?: StatsGroupBy;
    visitors_type?: StatsVisitorType;
    visitors_blog_id?: number;
    special_visitors_range: StatsRange;
    special_visitors_sort: string;
    special_visitors_size: number;
    special_visitors_group_by?: StatsGroupBy;
    special_visitors_type?: StatsVisitorType;
    special_visitors_blog_id?: number;
}

export interface StatsFilterConfig {
    bloggers?: readonly UserOption[];
    blogOptions: readonly BlogOption[];
    showBloggerFilter?: boolean;
    showBlogFilter?: boolean;
    showGroupByFilter?: boolean;
    showVisitorTypeFilter?: boolean;
    showRangeFilter?: boolean;
    blogFilterLabel?: string;
    sortOptions: readonly StatsSortOption[];
}

export interface StatsPageFilters {
    blog: FilterState;
    post: FilterState;
    visitor?: FilterState;
    specialVisitor?: FilterState;
}

export interface StatsPageData {
    blogs: BlogRow[];
    posts: PostRow[];
    visitorsFromPage?: VisitorRow[];
    visitorsFromSpecial?: VisitorRow[];
}

export interface StatsPageOptions {
    bloggers?: UserOption[];
    blogOptions: BlogOption[];
    postBlogOptions?: BlogOption[];
    visitorBlogOptions?: BlogOption[];
}

export interface StatsPageConfig {
    routeName: string;
    showBloggerFilter?: boolean;
    showBloggerColumn?: boolean;
    blogFilterLabel?: string;
}

export interface StatsPageProps {
    filters: StatsPageFilters;
    data: StatsPageData;
    options: StatsPageOptions;
    config: StatsPageConfig;
}

export interface StatsPageInertiaProps {
    blogFilters: FilterState;
    postFilters: FilterState;
    visitorFilters: FilterState;
    specialVisitorFilters: FilterState;
    blogs: BlogRow[];
    posts: PostRow[];
    visitorsFromPage: VisitorRow[];
    visitorsFromSpecial: VisitorRow[];
    bloggers?: UserOption[] | null;
    blogOptions: BlogOption[];
    postBlogOptions?: BlogOption[];
    visitorBlogOptions?: BlogOption[];
}

export function mapStatsPageProps(props: StatsPageInertiaProps, config: StatsPageConfig): StatsPageProps {
    return {
        config,
        data: {
            blogs: props.blogs,
            posts: props.posts,
            visitorsFromPage: props.visitorsFromPage,
            visitorsFromSpecial: props.visitorsFromSpecial,
        },
        filters: {
            blog: props.blogFilters,
            post: props.postFilters,
            visitor: props.visitorFilters,
            specialVisitor: props.specialVisitorFilters,
        },
        options: {
            bloggers: props.bloggers ?? undefined,
            blogOptions: props.blogOptions,
            postBlogOptions: props.postBlogOptions,
            visitorBlogOptions: props.visitorBlogOptions,
        },
    };
}

export interface BlogStats {
    id: number;
    name: string;
    posts_count: number;
    lifetime_views: number;
    daily_subscriptions_count: number;
    weekly_subscriptions_count: number;
}

export interface PostTimelineEntry {
    id: number;
    title: string;
    published_at: string;
    views: {
        total: number;
        year: number;
        half_year: number;
        month: number;
        week: number;
        day: number;
    };
}

export interface PostPerformanceEntry {
    id: number;
    title: string;
    ratio: number;
}

export interface PostsStats {
    timeline: PostTimelineEntry[];
    performance: PostPerformanceEntry[];
}

export interface UserAgentEntry {
    id: number;
    name: string;
}

export interface UserAgentStats {
    last_unique: UserAgentEntry[];
    last_added: UserAgentEntry[];
}

export interface BotViewEntry {
    id: number;
    name: string;
    matched_fragment?: string;
    hits: number;
    last_seen_at: string;
}

export interface BotStats {
    last_seen: BotViewEntry[];
    top_hits: BotViewEntry[];
    total_hits: number;
}
