import { computed } from 'vue';
import { FIXED_SIDEBAR_WIDTH, SIDEBAR_MAX_WIDTH, SIDEBAR_MIN_WIDTH } from '@/types/blog';

export type SidebarPosition = 'left' | 'right' | 'none';

interface PercentModeOptions {
    mode: 'percent';
    // numeric sidebar value (-50..50). Sign indicates side: negative -> left, positive -> right.
    sidebar?: number;
    minPercent?: number;
    maxPercent?: number;
}

interface FixedModeOptions {
    mode: 'fixed';
    sidebarPosition?: SidebarPosition;
    fixedWidthPx?: number;
}

export type UseSidebarLayoutOptions = PercentModeOptions | FixedModeOptions;

export function useSidebarLayout(options: UseSidebarLayoutOptions) {
    // Common helpers
    const isPercentMode = options.mode === 'percent';

    // Percent-based layout calculations (Landing page)
    const sidebarPercentage = computed<number>(() => {
        if (!isPercentMode) {
            return 0;
        }
        const value = options.sidebar ?? 0;
        return value;
    });

    const minPercent = isPercentMode ? (options.minPercent ?? SIDEBAR_MIN_WIDTH) : SIDEBAR_MIN_WIDTH;
    const maxPercent = isPercentMode ? (options.maxPercent ?? SIDEBAR_MAX_WIDTH) : SIDEBAR_MAX_WIDTH;

    const normalizedSidebarWidth = computed<number>(() => {
        if (isPercentMode) {
            // Clamp absolute value to min/max
            const abs = Math.abs(sidebarPercentage.value);
            return Math.min(maxPercent, Math.max(minPercent, abs));
        }
        // In fixed mode we expose the fixed pixel width via separate getter, but return 0 here for consistency
        return 0;
    });

    const hasSidebar = computed<boolean>(() => {
        if (isPercentMode) {
            return normalizedSidebarWidth.value > minPercent;
        }
        const pos = (options as FixedModeOptions).sidebarPosition ?? 'none';
        return pos !== 'none';
    });

    const isRightSidebar = computed<boolean>(() => {
        if (isPercentMode) {
            return sidebarPercentage.value > 0;
        }
        const pos = (options as FixedModeOptions).sidebarPosition ?? 'none';
        return pos === 'right';
    });

    const isLeftSidebar = computed<boolean>(() => {
        if (isPercentMode) {
            return sidebarPercentage.value < 0;
        }
        const pos = (options as FixedModeOptions).sidebarPosition ?? 'none';
        return pos === 'left';
    });

    // Styles
    const mainContentWidth = computed<number>(() => {
        if (isPercentMode) {
            return 100 - normalizedSidebarWidth.value;
        }
        return 100; // not used in fixed mode
    });

    const fixedWidthPx = computed<number>(() => {
        if (isPercentMode) {
            return 0;
        }
        return (options as FixedModeOptions).fixedWidthPx ?? FIXED_SIDEBAR_WIDTH;
    });

    const asideStyle = computed<Record<string, string>>(() => {
        if (!hasSidebar.value) {
            return {} as Record<string, string>;
        }
        if (isPercentMode) {
            const w = normalizedSidebarWidth.value + '%';
            return {
                width: w,
                flex: '0 0 ' + w,
            };
        }
        const px = fixedWidthPx.value + 'px';
        return {
            width: px,
            flex: '0 0 ' + px,
        };
    });

    const mainStyle = computed<Record<string, string>>(() => {
        if (!hasSidebar.value) {
            return {} as Record<string, string>;
        }
        if (isPercentMode) {
            const w = mainContentWidth.value + '%';
            return {
                width: w,
                flex: '1 1 ' + w,
            };
        }
        // Fixed mode: let it grow, keep min-width 0 to prevent overflow
        return {
            flex: '1 1 auto',
            minWidth: '0',
        };
    });

    const asideOrderClass = computed<string | undefined>(() => (isRightSidebar.value ? 'order-2' : undefined));
    const mainOrderClass = computed<string | undefined>(() => (isRightSidebar.value ? 'order-1' : undefined));

    return {
        // state
        hasSidebar,
        isLeftSidebar,
        isRightSidebar,
        // width helpers
        normalizedSidebarWidth,
        mainContentWidth,
        fixedWidthPx,
        // styles & order helpers
        asideStyle,
        mainStyle,
        asideOrderClass,
        mainOrderClass,
    };
}
