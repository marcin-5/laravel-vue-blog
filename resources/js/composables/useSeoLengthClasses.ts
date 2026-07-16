/**
 * Provides helper functions returning CSS classes based on the length of SEO-related fields.
 */
export function useSeoLengthClasses() {
    const getRangeClass = (value: string | null, from: number, to: number): string => {
        const length = value?.length || 0;
        return length >= from && length <= to ? 'bg-secondary' : '';
    };

    const getThresholdClass = (value: string | null, threshold1: number, threshold2: number): string => {
        const length = value?.length || 0;
        if (length > threshold2) {
            return 'bg-destructive text-destructive-foreground';
        }
        if (length > threshold1) {
            return 'bg-constructive text-constructive-foreground';
        }
        return '';
    };

    return { getRangeClass, getThresholdClass };
}
