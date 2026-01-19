import { i18n } from '@/i18n';

export function localizedName(name: string | Record<string, string> | null | undefined): string {
    const locale = (i18n.global.locale.value as string) || 'en';
    if (!name) return '';
    if (typeof name === 'string') return name;
    return name?.[locale] ?? name?.en ?? Object.values(name ?? {})[0] ?? '';
}
