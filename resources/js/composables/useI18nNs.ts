import { ensureNamespace } from '@/i18n';
import { onBeforeUnmount, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

/**
 * DRY helper for Vue i18n namespace loading in .vue files.
 *
 * Usage in <script setup>:
 *   const { t, ready } = await useI18nNs('welcome')
 *   // or multiple: await useI18nNs(['welcome', 'blog'])
 */
export async function useI18nNs(ns: string | string[]) {
    const namespaces = Array.isArray(ns) ? ns : [ns];
    const composer = useI18n();
    const ready = ref(false);

    // Re-ensure on locale change while component is alive
    // IMPORTANT: Register lifecycle hooks BEFORE any await statements
    const stop = watch(
        () => composer.locale.value,
        async (newLocale) => {
            for (const n of namespaces) {
                await ensureNamespace(newLocale, n, composer);
            }
        },
    );

    onBeforeUnmount(() => {
        stop();
    });

    // Initial ensure for current locale
    for (const n of namespaces) {
        await ensureNamespace(composer.locale.value, n, composer);
    }
    ready.value = true;

    return {
        t: composer.t,
        n: composer.n,
        d: composer.d,
        te: composer.te,
        tm: composer.tm,
        rt: composer.rt,
        locale: composer.locale,
        ready,
    } as const;
}
