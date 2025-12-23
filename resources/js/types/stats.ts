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
