<script lang="ts" setup>
import PublicNavbar from '@/components/PublicNavbar.vue';
import { useBlogTheme } from '@/composables/useBlogTheme';
import { useSidebarLayout } from '@/composables/useSidebarLayout';
import { SIDEBAR_MAX_WIDTH, SIDEBAR_MIN_WIDTH } from '@/types/blog';
import type { BlogTheme } from '@/types/blog.types';
import { computed } from 'vue';

const props = defineProps<{
    theme?: BlogTheme | null;
    sidebar?: number;
    isPublic?: boolean;
    maxWidthClass?: string;
}>();

const { hasSidebar, asideStyle, mainStyle, asideOrderClass, mainOrderClass, navbarMaxWidth } = useSidebarLayout({
    sidebar: props.sidebar,
    minPercent: SIDEBAR_MIN_WIDTH,
    maxPercent: SIDEBAR_MAX_WIDTH,
});

const { mergedThemeStyle } = useBlogTheme(computed(() => props.theme || undefined));

const containerClass = computed(() => [
    'mx-auto w-full p-4 sm:px-12 md:px-16',
    props.maxWidthClass ? props.maxWidthClass : hasSidebar.value ? 'max-w-screen-lg xl:max-w-screen-xl 2xl:max-w-screen-2xl' : 'max-w-screen-lg',
]);

const textClass = computed(() => (props.isPublic ? 'text-primary' : 'text-foreground'));
</script>

<template>
    <div :class="['flex min-h-screen flex-col bg-background antialiased', textClass]" :style="mergedThemeStyle">
        <PublicNavbar :maxWidth="navbarMaxWidth" />

        <div :class="containerClass">
            <slot name="top-divider"></slot>

            <!-- Stacked layout (Mobile/Tablet or no sidebar) -->
            <div :class="{ 'xl:hidden': hasSidebar }">
                <slot name="header"></slot>

                <main class="min-w-0 flex-1">
                    <slot name="content"></slot>
                </main>

                <slot name="middle-divider"></slot>

                <slot name="sidebar-content"></slot>
            </div>

            <!-- Desktop sidebar layout (xl+) -->
            <div v-if="hasSidebar" class="hidden items-start gap-8 xl:flex">
                <aside :class="asideOrderClass" :style="asideStyle">
                    <slot name="sidebar-content"></slot>
                </aside>

                <main :class="['min-w-0 flex-1', mainOrderClass]" :style="mainStyle">
                    <slot name="header"></slot>
                    <slot name="content"></slot>
                </main>
            </div>

            <slot name="navigation"></slot>
            <slot name="footer"></slot>
        </div>
    </div>
</template>
