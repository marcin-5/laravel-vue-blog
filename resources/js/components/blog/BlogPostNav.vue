<script lang="ts" setup>
import type { Navigation, NavPost } from '@/types/blog.types';
import { Link } from '@inertiajs/vue3';
import clsx from 'clsx';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { HomeIcon } from '@heroicons/vue/24/outline';
import BorderDivider from '@/components/blog/BorderDivider.vue';

type PostNavDirection = 'previous' | 'next';

interface PostNavItem {
    direction: PostNavDirection;
    post?: NavPost | null;
    labelKey: string;
    arrow: string;
    contentAlignmentClass: string;
}

const props = defineProps<{
    navigation?: Navigation;
    activeTag?: {
        id: number;
        name: string;
        slug: string;
    } | null;
}>();

const { t } = useI18n();

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

const getLinkStateClasses = (isClickable: boolean, ...extraClasses: string[]) =>
    clsx(NAV_LINK_CLASSES.base, ...extraClasses, isClickable ? NAV_LINK_CLASSES.active : NAV_LINK_CLASSES.inactive);

const getPostNavLinkClasses = (post?: NavPost | null) => getLinkStateClasses(!!post, 'gap-2');

const getBackLinkClasses = (isClickable: boolean) => getLinkStateClasses(isClickable, 'font-medium');

const getPostNavLabelClass = (direction: PostNavDirection) => clsx('text-xs', direction === 'previous' ? 'opacity-75' : 'opacity-90');

const getPostNavPost = (direction: PostNavDirection) => (direction === 'previous' ? props.navigation?.prevPost : props.navigation?.nextPost);

const createPostNavItem = (direction: PostNavDirection): PostNavItem => ({
    direction,
    post: getPostNavPost(direction),
    ...POST_NAV_ITEM_CONFIG[direction],
});

const postNavItems = computed(() => [createPostNavItem('previous'), createPostNavItem('next')]);

const getPostNavComponent = (post?: NavPost | null) => (post ? Link : 'span');
</script>

<template>
    <nav
        v-if="navigation && (navigation.nextPost || navigation.prevPost || !navigation.isLandingPage)"
        :aria-label="t('blog.post_nav.aria')"
        :style="{ fontFamily: 'var(--blog-nav-font)' }"
    >
        <BorderDivider class="my-4 pt-2" />

        <!-- Desktop Navigation -->
        <div class="hidden items-center justify-between gap-4 lg:flex">
            <component
                :is="getPostNavComponent(postNavItems[0].post)"
                :class="getPostNavLinkClasses(postNavItems[0].post)"
                :href="postNavItems[0].post?.url"
            >
                <span class="text-lg">{{ postNavItems[0].arrow }}</span>
                <div v-if="postNavItems[0].post" :class="postNavItems[0].contentAlignmentClass" class="flex flex-col">
                    <span :class="getPostNavLabelClass(postNavItems[0].direction)">{{ t(postNavItems[0].labelKey) }}</span>
                    <span class="font-medium">{{ postNavItems[0].post.title }}</span>
                </div>
                <span v-else>{{ t(postNavItems[0].labelKey) }}</span>
            </component>

            <component
                :is="navigation.isLandingPage ? 'span' : Link"
                :class="getBackLinkClasses(!navigation.isLandingPage)"
                :href="navigation.isLandingPage ? undefined : navigation.landingUrl"
            >
                {{ t('blog.post_nav.home_page') }}
            </component>

            <component
                :is="getPostNavComponent(postNavItems[1].post)"
                :class="getPostNavLinkClasses(postNavItems[1].post)"
                :href="postNavItems[1].post?.url"
            >
                <div v-if="postNavItems[1].post" :class="postNavItems[1].contentAlignmentClass" class="flex flex-col">
                    <span :class="getPostNavLabelClass(postNavItems[1].direction)">{{ t(postNavItems[1].labelKey) }}</span>
                    <span class="font-medium">{{ postNavItems[1].post.title }}</span>
                </div>
                <span v-else>{{ t(postNavItems[1].labelKey) }}</span>
                <span class="text-lg">{{ postNavItems[1].arrow }}</span>
            </component>
        </div>

        <!-- Mobile Navigation -->
        <div class="lg:hidden">
            <div class="flex flex-col gap-4 text-sm">
                <div v-if="navigation.prevPost" class="flex flex-col">
                    <span class="mb-1 text-xs opacity-70">← {{ t('blog.post_nav.previous') }}</span>
                    <Link :href="navigation.prevPost.url" class="font-medium text-primary hover:underline">
                        {{ navigation.prevPost.title }}
                    </Link>
                </div>

                <div v-if="navigation.nextPost" class="flex flex-col">
                    <span class="mb-1 text-xs opacity-70">{{ t('blog.post_nav.next') }} →</span>
                    <Link :href="navigation.nextPost.url" class="font-medium text-primary hover:underline">
                        {{ navigation.nextPost.title }}
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
