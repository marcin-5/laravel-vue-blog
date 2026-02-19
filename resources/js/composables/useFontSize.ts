import { onMounted, ref } from 'vue';

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
        const parsed = parseInt(savedSize, 10);
        if (!isNaN(parsed)) {
            applyFontSize(parsed);
            return;
        }
    }
    applyFontSize(DEFAULT_SIZE);
}

const fontSize = ref([DEFAULT_SIZE]);

export function useFontSize() {
    onMounted(() => {
        if (typeof window === 'undefined') {
            return;
        }

        const savedSize = localStorage.getItem(STORAGE_KEY);
        if (savedSize) {
            const parsed = parseInt(savedSize, 10);
            if (!isNaN(parsed)) {
                fontSize.value = [parsed];
            }
        }
    });

    function updateFontSize(newSize: number[] | undefined) {
        if (typeof window === 'undefined' || !newSize || newSize.length === 0) {
            return;
        }
        fontSize.value = newSize;
        localStorage.setItem(STORAGE_KEY, newSize[0].toString());
        applyFontSize(newSize[0]);
    }

    return {
        fontSize,
        updateFontSize,
    };
}
