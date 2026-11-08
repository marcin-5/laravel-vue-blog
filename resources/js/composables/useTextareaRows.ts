import { computed, ref, watch } from 'vue';

const DEFAULT_ROWS = 5;
const MIN_ROWS = 3;
const MAX_ROWS = 50;
const STORAGE_PREFIX = 'markdown-textarea-rows:';

function readRows(storageKey: string): number {
    if (typeof window === 'undefined') {
        return DEFAULT_ROWS;
    }

    try {
        const storedRows = window.localStorage.getItem(`${STORAGE_PREFIX}${storageKey}`);
        if (storedRows === null || !/^\d+$/.test(storedRows)) {
            return DEFAULT_ROWS;
        }

        return Math.min(MAX_ROWS, Math.max(MIN_ROWS, Number(storedRows)));
    } catch {
        return DEFAULT_ROWS;
    }
}

export function useTextareaRows(storageKey: string) {
    const rows = ref(readRows(storageKey));

    watch(rows, (value) => {
        if (typeof window === 'undefined') {
            return;
        }

        try {
            window.localStorage.setItem(`${STORAGE_PREFIX}${storageKey}`, String(value));
        } catch {
            // Storage can be unavailable in restricted browser contexts.
        }
    });

    const canDecrease = computed(() => rows.value > MIN_ROWS);
    const canIncrease = computed(() => rows.value < MAX_ROWS);

    function decrease() {
        if (canDecrease.value) {
            rows.value -= 1;
        }
    }

    function increase() {
        if (canIncrease.value) {
            rows.value += 1;
        }
    }

    return { rows, canDecrease, canIncrease, decrease, increase };
}