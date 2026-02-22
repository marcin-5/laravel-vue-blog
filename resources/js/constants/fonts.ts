export const FONT_CORRECTIONS: Record<string, number> = {
    'var(--font-yrsa)': 1.2,
    'var(--font-bitter)': 1.0,
    'var(--font-nunito)': 1.0,
    'var(--font-dm-sans)': 1.0,
    'var(--font-inter)': 1.0,
    'var(--font-montserrat)': 1.0,
    'var(--font-quicksand)': 1.0,
    'var(--font-raleway)': 1.0,
    'var(--font-recursive)': 1.0,
    'var(--font-roboto)': 1.0,
    'var(--font-faustina)': 1.0,
    'var(--font-literata)': 1.0,
    'var(--font-kreon)': 1.0,
    'var(--font-rokkitt)': 1.0,
    'var(--font-vollkorn)': 1.0,
    'var(--font-afacad)': 1.0,
    'var(--font-darker-grotesque)': 1.0,
    'var(--font-sofia-semi-condensed)': 1.0,
};

export function getFontCorrection(fontValue: string): number {
    return FONT_CORRECTIONS[fontValue] || 1.0;
}

export const SPECIAL_KEY_MAPPINGS: Record<string, string> = {
    '--motto-style': '--blog-motto-style',
    '--header-scale': '--blog-header-scale',
    '--body-scale': '--blog-body-scale',
    '--motto-scale': '--blog-motto-scale',
    '--excerpt-scale': '--blog-excerpt-scale',
    '--footer-scale': '--blog-footer-scale',
};

export const SCALE_TO_FONT_MAP: Record<string, string> = {
    '--header-scale': '--font-header',
    '--body-scale': '--font-body',
    '--motto-scale': '--font-motto',
    '--excerpt-scale': '--font-excerpt',
    '--footer-scale': '--font-footer',
};
