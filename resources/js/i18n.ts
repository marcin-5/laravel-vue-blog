import { createI18n } from 'vue-i18n';

export const i18n = createI18n({
    legacy: false,
    locale: 'en',
    fallbackLocale: 'en',
    messages: {},
    missingWarn: false,
    fallbackWarn: false,
});

// Supported locales configuration
const SUPPORTED_LOCALES = ['en', 'pl'] as const;
type SupportedLocale = (typeof SUPPORTED_LOCALES)[number];

// Track loaded namespaces to avoid redundant fetches
const loadedNamespaces = new Set<string>();

// Track in-progress loading promises to prevent concurrent duplicate requests
const loadingPromises = new Map<string, Promise<void>>();

function isValidLocale(locale: string): locale is SupportedLocale {
    return SUPPORTED_LOCALES.includes(locale as SupportedLocale);
}

function withBase(path: string) {
    // Fixed: Check for SSR (no window) instead of client
    if (typeof window === 'undefined') {
        const candidate =
            (process.env.SSR_BASE_URL as string | undefined) || (globalThis as any).__ziggyLocation || process.env.APP_URL || 'http://localhost';
        const base = candidate instanceof URL ? candidate.toString() : String(candidate);
        try {
            return new URL(path, base).toString();
        } catch {
            return path;
        }
    }
    return path;
}

export async function loadLocaleMessages(locale: string, retries = 3): Promise<Record<string, any>> {
    if (!isValidLocale(locale)) {
        throw new Error(`Unsupported locale: ${locale}`);
    }

    for (let attempt = 0; attempt <= retries; attempt++) {
        try {
            const res = await fetch(withBase(`/lang/${locale}`));
            if (!res.ok) {
                if (attempt === retries) {
                    throw new Error(`Failed to load messages after ${retries} retries`);
                }
                // Exponential backoff
                await new Promise((resolve) => setTimeout(resolve, 1000 * (attempt + 1)));
                continue;
            }
            const { messages } = await res.json();
            return messages as Record<string, any>;
        } catch (error) {
            if (attempt === retries) {
                throw error;
            }
        }
    }
    return {};
}

export async function loadNamespaceMessages(locale: string, namespace: string, retries = 3): Promise<Record<string, any>> {
    if (!isValidLocale(locale)) {
        throw new Error(`Unsupported locale: ${locale}`);
    }

    for (let attempt = 0; attempt <= retries; attempt++) {
        try {
            const res = await fetch(withBase(`/lang/${locale}/${namespace}`));
            if (!res.ok) {
                if (attempt === retries) {
                    throw new Error(`Failed to load namespace messages after ${retries} retries`);
                }
                // Exponential backoff
                await new Promise((resolve) => setTimeout(resolve, 1000 * (attempt + 1)));
                continue;
            }
            const { messages } = await res.json();
            return messages as Record<string, any>;
        } catch (error) {
            if (attempt === retries) {
                throw error;
            }
        }
    }
    return {};
}

function mergeDeep(target: Record<string, any>, source: Record<string, any>, seen = new WeakSet()): Record<string, any> {
    for (const key of Object.keys(source)) {
        if (source[key] && typeof source[key] === 'object' && !Array.isArray(source[key])) {
            // Prevent circular reference issues
            if (seen.has(source[key])) {
                target[key] = source[key];
                continue;
            }
            seen.add(source[key]);
            target[key] = mergeDeep(target[key] || {}, source[key], seen);
        } else {
            target[key] = source[key];
        }
    }
    return target;
}

export async function ensureNamespace(locale: string, namespace: string, i18nInstance: any = i18n): Promise<void> {
    if (!isValidLocale(locale)) {
        throw new Error(`Unsupported locale: ${locale}`);
    }

    const key = `${locale}:${namespace}`;

    // In SSR, avoid network fetches to prevent TLS/network issues.
    // Let the client load namespaces after hydration or rely on pre-provided messages.
    if (typeof window === 'undefined') {
        loadedNamespaces.add(key);
        return;
    }

    // Skip if already loaded
    if (loadedNamespaces.has(key)) {
        return;
    }

    // Return existing promise if already loading
    if (loadingPromises.has(key)) {
        return loadingPromises.get(key);
    }

    const loadPromise = (async () => {
        try {
            // Support both a full i18n instance (with .global) and a composer returned by useI18n()
            const api = i18nInstance && 'global' in i18nInstance ? i18nInstance.global : i18nInstance;
            if (!api) {
                throw new Error('Invalid i18n instance/composer passed to ensureNamespace');
            }

            // Ensure base locale object exists
            if (!api.availableLocales.includes(locale)) {
                api.setLocaleMessage(locale, {});
            }

            const current = api.getLocaleMessage(locale) as Record<string, any>;

            // We don't track per-namespace state; we just merge the file into the root keys
            const nsMsgs = await loadNamespaceMessages(locale, namespace);
            const merged = mergeDeep({ ...current }, nsMsgs);
            api.setLocaleMessage(locale, merged);

            // Mark as loaded
            loadedNamespaces.add(key);
        } finally {
            // Always clean up the loading promise
            loadingPromises.delete(key);
        }
    })();

    loadingPromises.set(key, loadPromise);
    await loadPromise;
}

export async function setLocale(locale: string, i18nInstance = i18n): Promise<void> {
    if (!isValidLocale(locale)) {
        throw new Error(`Unsupported locale: ${locale}`);
    }

    // Ensure locale container exists but do not load whole catalog; pages will lazy-load namespaces
    if (!i18nInstance.global.availableLocales.includes(locale)) {
        i18nInstance.global.setLocaleMessage(locale, {});
    }
    i18nInstance.global.locale.value = locale;
    if (typeof document !== 'undefined') {
        document.documentElement.setAttribute('lang', locale);
    }
}
