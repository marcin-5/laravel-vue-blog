<script lang="ts" setup>
import PublicNavbar from '@/components/PublicNavbar.vue';
import HeadSEO from '@/components/HeadSEO.vue';
import { useBlogTheme } from '@/composables/useBlogTheme';
import { useFontSetPreference } from '@/composables/useFontSetPreference';
import { useSidebarLayout } from '@/composables/useSidebarLayout';
import { SIDEBAR_MAX_WIDTH, SIDEBAR_MIN_WIDTH } from '@/types/blog';
import type { BlogTheme } from '@/types/blog.types';
import { computed } from 'vue';

const props = defineProps<{
    theme?: BlogTheme | null;
    sidebar?: number;
    isPublic?: boolean;
    maxWidthClass?: string;
    ownerType: 'blog' | 'group';
    ownerId: number;
}>();

const { hasSidebar, asideStyle, mainStyle, asideOrderClass, mainOrderClass, navbarMaxWidth } = useSidebarLayout({
    sidebar: props.sidebar,
    minPercent: SIDEBAR_MIN_WIDTH,
    maxPercent: SIDEBAR_MAX_WIDTH,
});

const fontSetPreference = useFontSetPreference(
    () => props.ownerType,
    () => props.ownerId,
);
const { mergedThemeStyle, hasAlternativeFonts } = useBlogTheme(
    computed(() => props.theme || undefined),
    fontSetPreference,
);

const containerClass = computed(() => [
    'mx-auto w-full p-4 sm:px-12 md:px-16',
    props.maxWidthClass
        ? props.maxWidthClass
        : hasSidebar.value
          ? 'max-w-screen-lg xl:max-w-screen-xl 2xl:max-w-screen-2xl'
          : props.isPublic
            ? 'max-w-screen-xl'
            : 'max-w-screen-lg',
]);

const textClass = computed(() => (props.isPublic ? 'text-primary' : 'text-foreground'));
</script>

<template>
    <HeadSEO />
    <div :class="['flex min-h-screen flex-col bg-background antialiased', textClass]" :style="mergedThemeStyle">
        <PublicNavbar
            :font-set-active="fontSetPreference"
            :has-alternative-fonts="hasAlternativeFonts"
            :maxWidth="props.isPublic && !hasSidebar ? 'max-w-screen-xl' : navbarMaxWidth"
            @toggle-font-set="fontSetPreference = !fontSetPreference"
        />

        <div :class="containerClass">
            <slot name="top-divider"></slot>

            <!-- Stacked layout (Mobile/Tablet or no sidebar) -->
            <div :class="{ 'xl:hidden': hasSidebar }">
                <slot name="header"></slot>

                <main class="min-w-0 flex-1">
                    <slot name="content"></slot>
                </main>

                <slot name="middle-divider"></slot>

                <slot name="post-navigation"></slot>

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
                    <slot name="middle-divider"></slot>
                    <slot name="post-navigation"></slot>
                </main>
            </div>

            <slot name="breadcrumbs"></slot>
            <slot name="footer"></slot>
        </div>
    </div>
</template>
