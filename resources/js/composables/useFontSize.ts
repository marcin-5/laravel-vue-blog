import { useLocalStorage } from '@vueuse/core';
import { onMounted } from 'vue';

const STORAGE_KEY = 'font-size-adjust';
const DEFAULT_SIZE = 100;

export function applyFontSize(size: number) {
    if (typeof window === 'undefined') {
        return;
    }
    document.documentElement.style.fontSize = `${size}%`;
}

export function initializeFontSize() {
    if (typeof window === 'undefined') {
        return;
    }

    const savedSize = localStorage.getItem(STORAGE_KEY);
    if (savedSize) {
        let sizeValue: number | null = null;
        try {
            const parsed = JSON.parse(savedSize);
            if (Array.isArray(parsed) && typeof parsed[0] === 'number') {
                sizeValue = parsed[0];
            } else if (typeof parsed === 'number') {
                sizeValue = parsed;
            }
        } catch (e) {
            const parsed = parseInt(savedSize, 10);
            if (!isNaN(parsed)) {
                sizeValue = parsed;
            }
        }

        if (sizeValue !== null) {
            applyFontSize(sizeValue);
            return;
        }
    }
    applyFontSize(DEFAULT_SIZE);
}

// Singleton state — shared across all component instances
const fontSize = useLocalStorage<number[]>(STORAGE_KEY, [DEFAULT_SIZE]);

export function useFontSize() {
    onMounted(() => {
        initializeFontSize();
    });

    function updateFontSize(newSize: number[] | undefined) {
        if (typeof window === 'undefined' || !newSize || newSize.length === 0) {
            return;
        }
        fontSize.value = newSize;
        applyFontSize(newSize[0]);
    }

    return {
        fontSize,
        updateFontSize,
    };
}
