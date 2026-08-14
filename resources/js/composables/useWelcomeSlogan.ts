import { usePage } from '@inertiajs/vue3';
import { computed, type ComputedRef } from 'vue';
import { useI18n } from 'vue-i18n';

export function normalizeSlogans(value: unknown): string[] {
    if (!Array.isArray(value)) {
        return [];
    }

    return value.filter((slogan): slogan is string => typeof slogan === 'string');
}

export function getStableSloganIndex(url: string, sloganCount: number): number {
    if (sloganCount <= 0) {
        return -1;
    }

    let hash = 0;

    for (let index = 0; index < url.length; index++) {
        hash = (hash * 31 + url.charCodeAt(index)) >>> 0;
    }

    return hash % sloganCount;
}

export interface WelcomeSloganState {
    slogan: ComputedRef<string>;
}

export function useWelcomeSlogan(): WelcomeSloganState {
    const page = usePage();
    const { tm } = useI18n();

    const slogan = computed(() => {
        const slogans = normalizeSlogans(tm('slogans'));

        if (slogans.length === 0) {
            return '';
        }

        const sloganIndex = getStableSloganIndex(page.url, slogans.length);

        return slogans[sloganIndex] ?? '';
    });

    return { slogan };
}
