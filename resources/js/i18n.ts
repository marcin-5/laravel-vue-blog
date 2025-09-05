import { createI18n } from 'vue-i18n'

export const i18n = createI18n({
  legacy: false,
  locale: 'en',
  fallbackLocale: 'en',
  messages: {},
  missingWarn: false,
  fallbackWarn: false,
})

export async function loadLocaleMessages(locale: string) {
  const res = await fetch(`/lang/${locale}`)
  if (!res.ok) throw new Error('Failed to load messages')
  const { messages } = await res.json()
  return messages as Record<string, any>
}

export async function loadNamespaceMessages(locale: string, namespace: string) {
  const res = await fetch(`/lang/${locale}/${namespace}`)
  if (!res.ok) throw new Error('Failed to load namespace messages')
  const { messages } = await res.json()
  return messages as Record<string, any>
}

function mergeDeep(target: Record<string, any>, source: Record<string, any>) {
  for (const key of Object.keys(source)) {
    if (source[key] && typeof source[key] === 'object' && !Array.isArray(source[key])) {
      target[key] = mergeDeep(target[key] || {}, source[key])
    } else {
      target[key] = source[key]
    }
  }
  return target
}

export async function ensureNamespace(locale: string, namespace: string, i18nInstance = i18n) {
  // Ensure base locale object exists
  if (!i18nInstance.global.availableLocales.includes(locale)) {
    i18nInstance.global.setLocaleMessage(locale, {})
  }
  const current = i18nInstance.global.getLocaleMessage(locale) as Record<string, any>
  // We don't track per-namespace state; we just merge the file into the root keys
  const nsMsgs = await loadNamespaceMessages(locale, namespace)
  const merged = mergeDeep({ ...current }, nsMsgs)
  i18nInstance.global.setLocaleMessage(locale, merged)
}

export async function setLocale(locale: string, i18nInstance = i18n) {
  // Ensure locale container exists but do not load whole catalog; pages will lazy-load namespaces
  if (!i18nInstance.global.availableLocales.includes(locale)) {
    i18nInstance.global.setLocaleMessage(locale, {})
  }
  i18nInstance.global.locale.value = locale
  document.documentElement.setAttribute('lang', locale)
}
