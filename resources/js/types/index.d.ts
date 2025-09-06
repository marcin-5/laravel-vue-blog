import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User | null;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
    // Optional list of roles allowed to see the item. If omitted or empty, visible to all.
    roles?: string[];
    // Optional HTTP method for Inertia Link. Defaults to 'get'.
    method?: 'get' | 'post' | 'put' | 'patch' | 'delete';
    // When true, the item should appear inactive/disabled and not trigger navigation/action.
    disabled?: boolean;
}

export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
};

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    role: string;
    blog_quota?: number | null;
    can_create_blog?: boolean;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;

export interface Category {
    id: number;
    name: string | Record<string, string>;
    slug?: string;
}

export interface PostItem {
    id: number;
    blog_id: number;
    title: string;
    excerpt: string | null;
    content?: string | null;
    is_published: boolean;
    visibility?: string;
    published_at?: string | null;
    created_at?: string | null;
}

export interface Blog {
    id: number;
    user_id: number;
    name: string;
    slug: string;
    description: string | null;
    is_published: boolean;
    locale: string;
    creation_date?: string | null;
    categories?: Category[];
    posts?: PostItem[];
}
