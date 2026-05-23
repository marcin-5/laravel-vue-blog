<script lang="ts" setup>
import BlogBreadcrumbs from '@/components/blog/BlogBreadcrumbs.vue';
import BorderDivider from '@/components/blog/BorderDivider.vue';
import type { Navigation, NavPost } from '@/types/blog.types';
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

const props = defineProps<{
    navigation?: Navigation;
}>();

const { t } = useI18n();

const LINK_STYLES = {
    base: 'inline-flex items-center rounded-sm px-3 py-2 text-xs md:text-sm transition-colors bg-card',
    active: 'border border-border text-primary hover:bg-secondary',
    inactive: 'border border-border text-primary opacity-50 cursor-default',
} as const;

const POST_NAV_CONFIG = {
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
    clsx(LINK_STYLES.base, ...extraClasses, isClickable ? LINK_STYLES.active : LINK_STYLES.inactive);

const getPostNavLinkClasses = (post?: NavPost | null) => getLinkStateClasses(!!post, 'gap-2');

const getBackLinkClasses = (isClickable: boolean) => getLinkStateClasses(isClickable, 'font-medium');

const breadcrumbs = computed(() => props.navigation?.breadcrumbs ?? []);

const createPostNavItem = (direction: PostNavDirection, post?: NavPost | null): PostNavItem => ({
    direction,
    post,
    ...POST_NAV_CONFIG[direction],
});

const previousPostNavItem = computed(() => createPostNavItem('previous', props.navigation?.prevPost));
const nextPostNavItem = computed(() => createPostNavItem('next', props.navigation?.nextPost));

const backLinkLabel = computed(() => t(props.navigation?.isGroup ? 'blog.post_nav.back_to_group' : 'blog.post_nav.back_to_blog'));
</script>

<template>
    <nav v-if="navigation" :aria-label="t('blog.post_nav.aria')" :style="{ fontFamily: 'var(--blog-nav-font)' }">
        <BorderDivider class="my-4 pt-2" />

        <BlogBreadcrumbs :breadcrumbs="breadcrumbs" />

        <BorderDivider class="mt-2 mb-4 pt-2" />

        <div class="flex items-center justify-between gap-4">
            <component
                :is="previousPostNavItem.post ? Link : 'span'"
                :class="getPostNavLinkClasses(previousPostNavItem.post)"
                :href="previousPostNavItem.post?.url"
            >
                <span class="text-lg">{{ previousPostNavItem.arrow }}</span>

                <div v-if="previousPostNavItem.post" :class="previousPostNavItem.contentAlignmentClass" class="flex flex-col">
                    <span class="text-xs opacity-75">{{ t(previousPostNavItem.labelKey) }}</span>
                    <span class="font-medium">{{ previousPostNavItem.post.title }}</span>
                </div>

                <span v-else>{{ t(previousPostNavItem.labelKey) }}</span>
            </component>

            <component
                :is="navigation.isLandingPage ? 'span' : Link"
                :class="getBackLinkClasses(!navigation.isLandingPage)"
                :href="navigation.isLandingPage ? undefined : navigation.landingUrl"
            >
                {{ backLinkLabel }}
            </component>

            <component
                :is="nextPostNavItem.post ? Link : 'span'"
                :class="getPostNavLinkClasses(nextPostNavItem.post)"
                :href="nextPostNavItem.post?.url"
            >
                <div v-if="nextPostNavItem.post" :class="nextPostNavItem.contentAlignmentClass" class="flex flex-col">
                    <span class="text-xs opacity-90">{{ t(nextPostNavItem.labelKey) }}</span>
                    <span class="font-medium">{{ nextPostNavItem.post.title }}</span>
                </div>

                <span v-else>{{ t(nextPostNavItem.labelKey) }}</span>

                <span class="text-lg">{{ nextPostNavItem.arrow }}</span>
            </component>
        </div>
    </nav>
</template>
