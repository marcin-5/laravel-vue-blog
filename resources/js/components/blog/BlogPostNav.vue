<script lang="ts" setup>
import BorderDivider from '@/components/blog/BorderDivider.vue';
import type { Navigation, NavPost } from '@/types/blog.types';
import { HomeIcon } from '@heroicons/vue/24/outline';
import { Link } from '@inertiajs/vue3';
import clsx from 'clsx';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

type PostNavDirection = 'previous' | 'next';

interface PostNavItem {
    direction: PostNavDirection;
    post?: NavPost | null;
    labelKey: string;
    arrow: string;
    contentAlignmentClass: string;
}

type LinkedPostNavItem = PostNavItem & {
    post: NavPost;
};

const props = defineProps<{
    navigation?: Navigation;
    activeTag?: {
        id: number;
        name: string;
        slug: string;
    } | null;
}>();

const { t } = useI18n();

const POST_NAV_DIRECTIONS = ['previous', 'next'] as const satisfies readonly PostNavDirection[];

const NAV_LINK_CLASSES = {
    base: 'inline-flex items-center rounded-sm px-3 py-2 text-xs md:text-sm transition-colors bg-card',
    active: 'border border-border text-primary hover:bg-secondary',
    inactive: 'border border-border text-primary opacity-50 cursor-default',
} as const;

const POST_NAV_ITEM_CONFIG = {
    previous: {
        labelKey: 'blog.post_nav.previous',
        arrow: '←',
        contentAlignmentClass: 'items-start',
    },
    next: {
        labelKey: 'blog.post_nav.next',
        arrow: '→',
        contentAlignmentClass: 'items-end',
    },
} as const satisfies Record<PostNavDirection, Omit<PostNavItem, 'direction' | 'post'>>;

const shouldShowNavigation = computed(
    () => !!props.navigation && (!!props.navigation.nextPost || !!props.navigation.prevPost || !props.navigation.isLandingPage),
);

const homeNavComponent = computed(() => (props.navigation?.isLandingPage ? 'span' : Link));
const homeNavHref = computed(() => (props.navigation?.isLandingPage ? undefined : props.navigation?.landingUrl));

const getLinkStateClasses = (isClickable: boolean, ...extraClasses: string[]) =>
    clsx(NAV_LINK_CLASSES.base, ...extraClasses, isClickable ? NAV_LINK_CLASSES.active : NAV_LINK_CLASSES.inactive);

const getPostNavLinkClasses = (post?: NavPost | null) => getLinkStateClasses(!!post, 'gap-2');
const getHomeLinkClasses = (isClickable: boolean) => getLinkStateClasses(isClickable, 'font-medium');

const getPostNavLabelClass = (direction: PostNavDirection) => clsx('text-xs', direction === 'previous' ? 'opacity-75' : 'opacity-90');

const getPostNavPost = (direction: PostNavDirection) => (direction === 'previous' ? props.navigation?.prevPost : props.navigation?.nextPost);

const createPostNavItem = (direction: PostNavDirection): PostNavItem => ({
    direction,
    post: getPostNavPost(direction),
    ...POST_NAV_ITEM_CONFIG[direction],
});

const postNavItems = computed(() => POST_NAV_DIRECTIONS.map(createPostNavItem));

const linkedPostNavItems = computed<LinkedPostNavItem[]>(() => postNavItems.value.filter((item): item is LinkedPostNavItem => !!item.post));

const getPostNavComponent = (post?: NavPost | null) => (post ? Link : 'span');

const isPreviousPostNavItem = (item: PostNavItem) => item.direction === 'previous';

const getMobilePostNavLabel = (item: PostNavItem) =>
    isPreviousPostNavItem(item) ? `${item.arrow} ${t(item.labelKey)}` : `${t(item.labelKey)} ${item.arrow}`;
</script>

<template>
    <nav v-if="shouldShowNavigation && navigation" :aria-label="t('blog.post_nav.aria')" :style="{ fontFamily: 'var(--blog-nav-font)' }">
        <BorderDivider class="my-4 pt-2" />

        <!-- Desktop Navigation -->
        <div class="hidden items-center justify-between gap-4 lg:flex">
            <component
                :is="getPostNavComponent(item.post)"
                v-for="item in postNavItems"
                :key="item.direction"
                :class="[getPostNavLinkClasses(item.post), { 'order-1': item.direction === 'previous', 'order-3': item.direction === 'next' }]"
                :href="item.post?.url"
            >
                <span v-if="isPreviousPostNavItem(item)" class="text-lg">{{ item.arrow }}</span>

                <div v-if="item.post" :class="item.contentAlignmentClass" class="flex flex-col">
                    <span :class="getPostNavLabelClass(item.direction)">{{ t(item.labelKey) }}</span>
                    <span class="font-medium">{{ item.post.title }}</span>
                </div>
                <span v-else>{{ t(item.labelKey) }}</span>

                <span v-if="!isPreviousPostNavItem(item)" class="text-lg">{{ item.arrow }}</span>
            </component>

            <component :is="homeNavComponent" :class="getHomeLinkClasses(!navigation.isLandingPage)" :href="homeNavHref" class="order-2">
                {{ t('blog.post_nav.home_page') }}
            </component>
        </div>

        <!-- Mobile Navigation -->
        <div class="lg:hidden">
            <div class="flex flex-col gap-4 text-sm">
                <div v-for="item in linkedPostNavItems" :key="item.direction" class="flex flex-col">
                    <span class="mb-1 text-xs opacity-70">{{ getMobilePostNavLabel(item) }}</span>
                    <Link :href="item.post.url" class="font-medium text-primary hover:underline">
                        {{ item.post.title }}
                    </Link>
                </div>

                <div v-if="!navigation.isLandingPage">
                    <Link
                        :href="navigation.landingUrl"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold tracking-wider uppercase opacity-60 transition-opacity hover:opacity-100"
                    >
                        <HomeIcon class="h-4 w-4 shrink-0" />
                        <span>{{ t('blog.post_nav.home_page') }}</span>
                    </Link>
                </div>
            </div>
        </div>
    </nav>
</template>
