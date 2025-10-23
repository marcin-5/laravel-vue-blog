import { useI18nNs } from '@/composables/useI18nNs';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

/**
 * useI18nGate
 * Reusable helper to ensure i18n messages are loaded before rendering.
 * - Optionally preloads one or more namespaces via useI18nNs.
 * - Exposes a reactive `ready` boolean and passes through t/d/n helpers.
 *
 * Usage (in <script setup>):
 *   const { ready: i18nReady, t } = await useI18nGate('welcome');
 */
export async function useI18nGate(ns?: string | string[]) {
    const namespaces = (Array.isArray(ns) ? ns : ns ? [ns] : []) as string[];

    // IMPORTANT: Call useI18n() BEFORE any await statements to register lifecycle hooks properly
    const { locale, messages, t, d, n } = useI18n();
    const ready = computed(() => !!messages.value[locale.value]);

    // Preload provided namespaces (safe to await under <script setup> + Suspense)
    if (namespaces.length > 0) {
        await Promise.all(namespaces.map((n) => useI18nNs(n)));
    }

    return {
        ready,
        t,
        d,
        n,
    } as const;
}
