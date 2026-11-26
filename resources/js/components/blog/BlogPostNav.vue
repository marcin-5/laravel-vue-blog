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
    () =>
        !!props.navigation &&
        (!!props.navigation.nextPost || !!props.navigation.prevPost || !!props.navigation.recommendedLinksUrl || !props.navigation.isLandingPage),
);

const homeNavComponent = computed(() => (props.navigation?.isLandingPage ? 'span' : Link));
const homeNavHref = computed(() => (props.navigation?.isLandingPage ? undefined : props.navigation?.landingUrl));

const getLinkStateClasses = (isClickable: boolean, ...extraClasses: string[]) =>
    clsx(NAV_LINK_CLASSES.base, ...extraClasses, isClickable ? NAV_LINK_CLASSES.active : NAV_LINK_CLASSES.inactive);

const getPostNavLinkClasses = (post?: NavPost | null) => getLinkStateClasses(!!post, 'min-w-0 max-w-[45%] gap-2');
const getHomeLinkClasses = (isClickable: boolean) => getLinkStateClasses(isClickable, 'font-medium');

const getPostNavLabelKey = (direction: PostNavDirection) =>
    direction === 'next' && props.navigation?.isLandingPage ? 'blog.post_nav.latest_post' : POST_NAV_ITEM_CONFIG[direction].labelKey;

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
    isPreviousPostNavItem(item) ? `${item.arrow} ${t(item.labelKey)}` : `${t(getPostNavLabelKey(item.direction))} ${item.arrow}`;
</script>

<template>
    <nav v-if="shouldShowNavigation && navigation" :aria-label="t('blog.post_nav.aria')" :style="{ fontFamily: 'var(--blog-nav-font)' }">
        <!-- Desktop Navigation -->
        <div v-if="navigation.isLandingPage" class="desktop-navigation hidden items-stretch justify-between gap-4 lg:flex">
            <Link
                v-if="navigation.recommendedLinksUrl"
                :class="getLinkStateClasses(true, 'max-w-[45%] min-w-0 gap-2')"
                :href="navigation.recommendedLinksUrl"
            >
                {{ t('blog.recommended_links.heading') }}
            </Link>
            <component
                :is="getPostNavComponent(navigation.nextPost)"
                :class="[getPostNavLinkClasses(navigation.nextPost), 'ml-auto']"
                :href="navigation.nextPost?.url"
            >
                <div v-if="navigation.nextPost" class="flex min-w-0 flex-col items-end">
                    <span class="text-xs opacity-90">{{ t('blog.post_nav.latest_post') }}</span>
                    <span :title="navigation.nextPost.title" class="block w-full truncate font-medium">{{ navigation.nextPost.title }}</span>
                </div>
                <span v-else>{{ t('blog.post_nav.latest_post') }}</span>
                <span class="text-lg">→</span>
            </component>
        </div>

        <div v-else class="desktop-navigation hidden items-stretch justify-between gap-4 lg:flex">
            <component
                :is="getPostNavComponent(item.post)"
                v-for="item in postNavItems"
                :key="item.direction"
                :class="[getPostNavLinkClasses(item.post), { 'order-1': item.direction === 'previous', 'order-3': item.direction === 'next' }]"
                :href="item.post?.url"
            >
                <span v-if="isPreviousPostNavItem(item)" class="text-lg">{{ item.arrow }}</span>

                <div v-if="item.post" :class="item.contentAlignmentClass" class="flex min-w-0 flex-col">
                    <span :class="getPostNavLabelClass(item.direction)">{{ t(getPostNavLabelKey(item.direction)) }}</span>
                    <span :title="item.post.title" class="block w-full truncate font-medium">{{ item.post.title }}</span>
                </div>
                <span v-else>{{ t(item.labelKey) }}</span>

                <span v-if="!isPreviousPostNavItem(item)" class="text-lg">{{ item.arrow }}</span>
            </component>

            <component :is="homeNavComponent" :class="getHomeLinkClasses(!navigation.isLandingPage)" :href="homeNavHref" class="order-2">
                {{ t('blog.post_nav.home_page') }}
            </component>
            <Link
                v-if="navigation.recommendedLinksUrl"
                :href="navigation.recommendedLinksUrl"
                class="order-4 inline-flex items-center rounded-sm border border-border bg-card px-3 py-2 text-xs text-primary transition-colors hover:bg-secondary md:text-sm"
            >
                {{ t('blog.recommended_links.heading') }}
            </Link>
        </div>

        <!-- Mobile Navigation -->
        <div class="mobile-navigation lg:hidden">
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

                <Link v-if="navigation.recommendedLinksUrl" :href="navigation.recommendedLinksUrl" class="font-medium text-primary hover:underline">
                    {{ t('blog.recommended_links.heading') }}
                </Link>
            </div>
        </div>

        <BorderDivider class="my-4" />
    </nav>
</template>
