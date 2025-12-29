export interface CategoryRow {
    id: number;
    name: string | Record<string, string>;
    slug: string;
    blogs_count?: number;
}

export interface NewsletterSubscription {
    email: string;
    subscriptions: {
        blog: string;
        frequency: string;
    }[];
}
