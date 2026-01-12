import { computed } from 'vue';
import { SIDEBAR_MAX_WIDTH, SIDEBAR_MIN_WIDTH } from '@/types/blog';

// Tailwind xl breakpoint width (used for calculating sidebar width from percentage)
const XL_BREAKPOINT_WIDTH = 1280;

export interface UseSidebarLayoutOptions {
    // numeric sidebar value (-50..50). Sign indicates side: negative -> left, positive -> right, 0 -> no sidebar
    sidebar?: number;
    minPercent?: number;
    maxPercent?: number;
}

export function useSidebarLayout(options: UseSidebarLayoutOptions) {
    const sidebarValue = computed<number>(() => options.sidebar ?? 0);

    const minPercent = options.minPercent ?? SIDEBAR_MIN_WIDTH;
    const maxPercent = options.maxPercent ?? SIDEBAR_MAX_WIDTH;

    // Normalized sidebar width (absolute value, clamped to min/max)
    const normalizedSidebarWidth = computed<number>(() => {
        const abs = Math.abs(sidebarValue.value);
        return Math.min(maxPercent, Math.max(minPercent, abs));
    });

    // Calculate pixel width based on xl breakpoint
    const sidebarWidthPx = computed<number>(() => {
        return Math.round((normalizedSidebarWidth.value / 100) * XL_BREAKPOINT_WIDTH);
    });

    const hasSidebar = computed<boolean>(() => {
        return normalizedSidebarWidth.value > minPercent;
    });

    const isRightSidebar = computed<boolean>(() => {
        return sidebarValue.value > 0;
    });

    const isLeftSidebar = computed<boolean>(() => {
        return sidebarValue.value < 0;
    });

    // Styles - using fixed pixel width based on xl breakpoint
    const asideStyle = computed<Record<string, string>>(() => {
        if (!hasSidebar.value) {
            return {} as Record<string, string>;
        }
        const px = sidebarWidthPx.value + 'px';
        return {
            width: px,
            flex: '0 0 ' + px,
        };
    });

    const mainStyle = computed<Record<string, string>>(() => {
        if (!hasSidebar.value) {
            return {} as Record<string, string>;
        }
        // Let main content grow and shrink, with min-width 0 to prevent overflow
        return {
            flex: '1 1 auto',
            minWidth: '0',
        };
    });

    const asideOrderClass = computed<string | undefined>(() => (isRightSidebar.value ? 'order-2' : undefined));
    const mainOrderClass = computed<string | undefined>(() => (isRightSidebar.value ? 'order-1' : undefined));

    // Navbar max-width class based on sidebar layout
    const navbarMaxWidth = computed<string>(() => (hasSidebar.value ? 'max-w-screen-lg xl:max-w-screen-xl 2xl:max-w-screen-2xl' : 'max-w-screen-lg'));

    return {
        // state
        hasSidebar,
        isLeftSidebar,
        isRightSidebar,
        // width helpers
        normalizedSidebarWidth,
        sidebarWidthPx,
        // styles & order helpers
        asideStyle,
        mainStyle,
        asideOrderClass,
        mainOrderClass,
        navbarMaxWidth,
    };
}
