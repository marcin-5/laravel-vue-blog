// Consolidated blog-related types (public + admin)

// Public blog types (from resources/js/types/blog.ts)
import { useForm } from '@inertiajs/vue3';

export type BlogTheme = {
    light?: Record<string, string>;
    dark?: Record<string, string>;
};

export interface Blog {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
    descriptionHtml?: string | null;
    motto?: string | null;
    theme?: BlogTheme;
}

export interface BlogItem extends Blog {
    author: string;
    categories: CategoryItem[];
}

export interface PostItem {
    id: number;
    title: string;
    slug: string;
    excerpt?: string | null;
    published_at?: string | null;
}

export interface PostDetails extends PostItem {
    author: string;
    author_email: string | null;
    contentHtml: string;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Pagination {
    links: PaginationLink[];
    prevUrl?: string | null;
    nextUrl?: string | null;
}

export interface NavPost {
    title: string;
    slug: string;
    url: string;
}

export interface BreadcrumbItem {
    label: string;
    url?: string | null;
}

export interface Navigation {
    prevPost?: NavPost | null;
    nextPost?: NavPost | null;
    landingUrl: string;
    isLandingPage?: boolean;
    breadcrumbs?: BreadcrumbItem[];
}

export interface CategoryItem {
    id: number;
    name: string | Record<string, string>;
    slug?: string;
}

export type SidebarPosition = 'left' | 'right' | 'none';

// Admin types (from resources/js/types/index.d.ts)
export interface Category {
    id: number;
    name: string | Record<string, string>;
    slug?: string;
}

export interface AdminPostItem {
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

export interface AdminBlog {
    id: number;
    user_id: number;
    name: string;
    slug: string;
    description: string | null;
    motto?: string | null;
    footer?: string | null;
    is_published: boolean;
    locale: string;
    sidebar?: number; // -50..50
    page_size?: number; // default 10
    creation_date?: string | null;
    categories?: Category[];
    posts?: AdminPostItem[];
    theme?: BlogTheme;
}

// ===== Blog form/composable shared types =====
export interface BlogFormData {
    name: string;
    description: string | null;
    footer: string | null;
    motto: string | null;
    is_published: boolean;
    locale: string;
    sidebar: number;
    page_size: number;
    categories: number[];
    theme?: BlogTheme | null;
    [key: string]: any;
}

export type InertiaForm<T extends Record<string, any>> = ReturnType<typeof useForm<T>>;

export interface UseBlogFormLogicOptions {
    blog?: AdminBlog;
    isEdit?: boolean;
    externalForm?: InertiaForm<BlogFormData>;
}
