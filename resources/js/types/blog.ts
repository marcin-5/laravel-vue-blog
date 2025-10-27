// Constants
export const SIDEBAR_MIN_WIDTH = 0;
export const SIDEBAR_MAX_WIDTH = 50;
export const EXCERPT_MAX_LENGTH = 200;
export const DEFAULT_APP_URL = 'https://osobliwy.blog';
export const FIXED_SIDEBAR_WIDTH = 280;

// Utility functions
export function stripHtmlTags(html: string): string {
    return html.replace(/<[^>]*>/g, '');
}

export function stripCutMarkers(html: string): string {
    return html.replace(/-!-/g, '');
}

export function cutMarkedSection(html: string): string {
    return html.replace(/-!-.*-!-/gms, '');
}

import type { CategoryItem } from './blog.types';

export function getCategoryDisplayName(category: CategoryItem): string {
    return typeof category.name === 'string' ? category.name : '';
}
