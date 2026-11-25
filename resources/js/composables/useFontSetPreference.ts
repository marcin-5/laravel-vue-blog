import { computed, ref, toValue, watch, type MaybeRefOrGetter } from 'vue';

export type FontSetOwnerType = 'blog' | 'group';

export function useFontSetPreference(ownerType: MaybeRefOrGetter<FontSetOwnerType>, ownerId: MaybeRefOrGetter<number>) {
    const storageKey = computed(() => `font-set:${toValue(ownerType)}:${toValue(ownerId)}`);
    const preference = ref(false);

    watch(
        storageKey,
        (key) => {
            preference.value = typeof localStorage !== 'undefined' && localStorage.getItem(key) === 'true';
        },
        { immediate: true, flush: 'sync' },
    );

    watch(
        preference,
        (value) => {
            if (typeof localStorage !== 'undefined') {
                localStorage.setItem(storageKey.value, String(value));
            }
        },
        { flush: 'sync' },
    );

    return preference;
}