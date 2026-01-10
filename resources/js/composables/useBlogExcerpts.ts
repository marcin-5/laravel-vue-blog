import { computed, ref } from 'vue';

// Store states per blog slug to avoid conflicts if navigating between blogs
const excerptsStates = ref<Record<string, boolean>>({});
const initializedBlogs = new Set<string>();

export function useBlogExcerpts(blogSlug: string) {
    const storageKey = `blog_show_excerpts_${blogSlug}`;

    // Initialize state for this blog if not already done
    if (!initializedBlogs.has(blogSlug)) {
        initializedBlogs.add(blogSlug);

        // Default to true, then try to load from localStorage (client-side only)
        excerptsStates.value[blogSlug] = true;

        if (typeof window !== 'undefined') {
            const saved = localStorage.getItem(storageKey);
            if (saved !== null) {
                excerptsStates.value[blogSlug] = saved === 'true';
            }
        }
    }

    const showExcerpts = computed({
        get: () => excerptsStates.value[blogSlug] ?? true,
        set: (value: boolean) => {
            excerptsStates.value[blogSlug] = value;
            if (typeof window !== 'undefined') {
                localStorage.setItem(storageKey, String(value));
            }
        },
    });

    return { showExcerpts };
}
