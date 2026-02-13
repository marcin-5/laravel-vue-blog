export const ONE_DAY_MS = 24 * 60 * 60 * 1000;

export function isValidDate(dateString: string | null | undefined): boolean {
    if (!dateString) return false;
    const date = new Date(dateString);
    return !isNaN(date.getTime());
}

export function formatDate(dateString: string | null | undefined, locale?: string): string {
    if (!dateString) return '';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return String(dateString);

    try {
        return new Intl.DateTimeFormat(locale || undefined, {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        }).format(date);
    } catch {
        return date.toLocaleDateString();
    }
}

export function formatDateTime(dateString: string | null | undefined, locale?: string): string {
    if (!dateString) return '';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return String(dateString);

    try {
        return new Intl.DateTimeFormat(locale || undefined, {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
        }).format(date);
    } catch {
        return date.toLocaleString();
    }
}

export function shouldShowUpdatedDate(publishedTime: string | null | undefined, modifiedTime: string | null | undefined): boolean {
    if (!isValidDate(publishedTime) || !isValidDate(modifiedTime)) return false;
    const published = new Date(publishedTime!);
    const modified = new Date(modifiedTime!);
    return Math.abs(modified.getTime() - published.getTime()) > ONE_DAY_MS;
}
