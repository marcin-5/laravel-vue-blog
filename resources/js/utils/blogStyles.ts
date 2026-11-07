export const tagChipBaseClasses = 'rounded-full border px-2 py-0.5 font-medium transition-colors';
export const tagChipActiveClasses = 'border-link bg-link/10 text-foreground';
export const tagChipInactiveClasses = 'border-border bg-card text-muted-foreground hover:border-link hover:text-link';
export const clearTagFilterClasses = 'border-link bg-link-hover/10 text-link-hover';

export const blogBodyTextStyle = {
    fontFamily: 'var(--blog-body-font)',
    fontSize: 'calc(1rem * var(--blog-body-scale))',
};

export const blogHeaderStyle = {
    fontFamily: 'var(--blog-header-font)',
    fontSize: 'calc(1.25rem * var(--blog-header-scale))',
};

export const tagChipSizeClasses = {
    sm: 'text-sm',
    xs: 'text-xs',
} as const;

export function getTagChipClasses(isActive: boolean, size: keyof typeof tagChipSizeClasses = 'sm'): string[] {
    return [tagChipBaseClasses, tagChipSizeClasses[size], isActive ? tagChipActiveClasses : tagChipInactiveClasses];
}
