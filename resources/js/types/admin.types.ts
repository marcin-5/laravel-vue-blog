export interface CategoryRow {
    id: number;
    name: string | Record<string, string>;
    slug: string;
    blogs_count?: number;
}

export interface BlogVerificationRow {
    id: number;
    name: string;
    owner_name: string;
    bing_site_verification_token: string | null;
}

export type Role = 'admin' | 'blogger' | 'user';

export interface UserWithQuota {
    id: number;
    role: Role;
    blog_quota: number | null;
}

export interface UserRow extends UserWithQuota {
    name: string;
    email: string;
}

export interface NewsletterSubscription {
    email: string;
    subscriptions: {
        blog: string;
        frequency: string;
    }[];
}
