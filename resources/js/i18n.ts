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

function isValidLocale(locale: string): locale is SupportedLocale {
    return SUPPORTED_LOCALES.includes(locale as SupportedLocale);
}

export async function setLocale(locale: string, i18nInstance = i18n): Promise<void> {
    if (!isValidLocale(locale)) {
        throw new Error(`Unsupported locale: ${locale}`);
    }

    // Ensure locale container exists
    if (!i18nInstance.global.availableLocales.includes(locale)) {
        i18nInstance.global.setLocaleMessage(locale, {});
    }
    i18nInstance.global.locale.value = locale;
    if (typeof document !== 'undefined') {
        document.documentElement.setAttribute('lang', locale);
    }
}
