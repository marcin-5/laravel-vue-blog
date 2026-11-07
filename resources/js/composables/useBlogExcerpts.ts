import { useLocalStorage } from '@vueuse/core';
import { computed, toValue, type MaybeRefOrGetter, type Ref } from 'vue';

// Singleton state — shared across all component instances
// Store states per blog slug to avoid conflicts if navigating between blogs
const excerptsStates: Record<string, Ref<boolean>> = {};

function getExcerptsState(blogSlug: string): Ref<boolean> {
    if (!excerptsStates[blogSlug]) {
        excerptsStates[blogSlug] = useLocalStorage(`blog_show_excerpts_${blogSlug}`, true);
    }

    return excerptsStates[blogSlug];
}

export function useBlogExcerpts(blogSlug: MaybeRefOrGetter<string>) {
    const showExcerpts = computed({
        get: () => getExcerptsState(toValue(blogSlug)).value,
        set: (value: boolean) => {
            getExcerptsState(toValue(blogSlug)).value = value;
        },
    });

    return { showExcerpts };
}
