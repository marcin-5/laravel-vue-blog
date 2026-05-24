import { useLocalStorage } from '@vueuse/core';

// Singleton state — shared across all component instances
// Store states per blog slug to avoid conflicts if navigating between blogs
const excerptsStates: Record<string, any> = {};

export function useBlogExcerpts(blogSlug: string) {
    const storageKey = `blog_show_excerpts_${blogSlug}`;

    // Initialize state for this blog if not already done
    if (!excerptsStates[blogSlug]) {
        excerptsStates[blogSlug] = useLocalStorage(storageKey, true);
    }

    const showExcerpts = excerptsStates[blogSlug];

    return { showExcerpts };
}
