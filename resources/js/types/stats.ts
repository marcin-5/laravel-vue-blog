export type StatsRange = 'today' | 'week' | 'month' | 'half_year' | 'year';

export type BlogRow = {
    blog_id: number;
    name: string;
    owner_id: number;
    owner_name: string;
    views: number;
    post_views: number;
};

export type PostRow = {
    post_id: number;
    title: string;
    views: number;
};

export type VisitorRow = {
    visitor_label: string;
    blog_views: number;
    post_views: number;
    views: number;
    lifetime_views: number;
};

export type UserOption = { id: number; name: string };
export type BlogOption = { id: number; name: string };

export interface FilterState {
    range: StatsRange;
    sort: string;
    size: number;
    blogger_id?: number | null;
    blog_id?: number | null;
    group_by?: 'visitor_id' | 'fingerprint';
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
