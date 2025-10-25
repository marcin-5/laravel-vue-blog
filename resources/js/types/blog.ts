// Shared type definitions for blog-related components

export interface Blog {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
    descriptionHtml?: string | null;
    motto?: string | null;
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
}

export interface NavPost {
    title: string;
    slug: string;
    url: string;
}

export interface Navigation {
    prevPost?: NavPost | null;
    nextPost?: NavPost | null;
    landingUrl: string;
    isLandingPage?: boolean;
}

export interface SEO {
    title: string;
    description: string;
    canonicalUrl: string;
    ogImage: string;
    ogType: string;
    locale: string;
    publishedTime?: string | null;
    modifiedTime?: string | null;
    structuredData: Record<string, any>;
}

export interface CategoryItem {
    id: number;
    name: string | Record<string, string>;
    slug?: string;
}

export type SidebarPosition = 'left' | 'right' | 'none';

// Constants
export const SIDEBAR_MIN_WIDTH = 0;
export const SIDEBAR_MAX_WIDTH = 50;
export const EXCERPT_MAX_LENGTH = 200;
export const DEFAULT_APP_URL = 'https://osobliwy.blog';
export const FIXED_SIDEBAR_WIDTH = 280;
