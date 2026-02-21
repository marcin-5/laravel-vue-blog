export const BLOG_SORT_OPTIONS = [
    { value: 'views_desc', label: 'Views ↓' },
    { value: 'views_asc', label: 'Views ↑' },
    { value: 'name_asc', label: 'Name A→Z' },
    { value: 'name_desc', label: 'Name Z→A' },
] as const;

export const POST_SORT_OPTIONS = [
    { value: 'views_desc', label: 'Views ↓' },
    { value: 'views_asc', label: 'Views ↑' },
    { value: 'title_asc', label: 'Title A→Z' },
    { value: 'title_desc', label: 'Title Z→A' },
] as const;

export const VISITOR_SORT_OPTIONS = [
    { value: 'views_desc', label: 'Post views ↓' },
    { value: 'views_asc', label: 'Post views ↑' },
    { value: 'name_asc', label: 'Name A→Z' },
    { value: 'name_desc', label: 'Name Z→A' },
] as const;

export const SPECIAL_VISITOR_SORT_OPTIONS = [
    { value: 'views_desc', label: 'Post views ↓' },
    { value: 'views_asc', label: 'Post views ↑' },
    { value: 'last_seen_desc', label: 'Last seen ↓' },
    { value: 'last_seen_asc', label: 'Last seen ↑' },
    { value: 'name_asc', label: 'Name A→Z' },
    { value: 'name_desc', label: 'Name Z→A' },
] as const;
