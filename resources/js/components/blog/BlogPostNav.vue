<script lang="ts" setup>
import BorderDivider from '@/components/blog/BorderDivider.vue';
import type { Navigation, NavPost } from '@/types/blog.types';
import { Link } from '@inertiajs/vue3';
import clsx from 'clsx';
import { useI18n } from 'vue-i18n';

defineProps<{
    navigation?: Navigation;
}>();

const { t } = useI18n();

const ARROW_LEFT = '←';
const ARROW_RIGHT = '→';

const BASE_LINK_CLASSES = 'inline-flex items-center rounded-sm px-3 py-2 text-sm transition-colors bg-card';
const ACTIVE_LINK_CLASSES = 'border border-border text-foreground hover:bg-secondary';
const INACTIVE_LINK_CLASSES = 'border border-border text-foreground opacity-50 cursor-default';

const getNavLinkClasses = (post: NavPost | null | undefined) => clsx(BASE_LINK_CLASSES, 'gap-2', post ? ACTIVE_LINK_CLASSES : INACTIVE_LINK_CLASSES);

const getBackLinkClasses = (isClickable: boolean) =>
    clsx(BASE_LINK_CLASSES, 'font-medium', isClickable ? ACTIVE_LINK_CLASSES : INACTIVE_LINK_CLASSES);

const isLastBreadcrumb = (index: number, total: number) => index === total - 1;

const isBreadcrumbLink = (index: number, total: number, url?: string | null) => !isLastBreadcrumb(index, total) && !!url;

const getBreadcrumbClasses = (index: number, total: number) =>
    clsx('hover:underline', isLastBreadcrumb(index, total) ? 'text-breadcrumb-link-active' : 'text-breadcrumb-link');
</script>

<template>
    <nav v-if="navigation" :aria-label="t('blog.post_nav.aria')" :style="{ fontFamily: 'var(--blog-nav-font)' }">
        <BorderDivider class="my-4 pt-2" />

        <!-- Breadcrumbs -->
        <ol v-if="navigation.breadcrumbs?.length" aria-label="Breadcrumb" class="flex flex-wrap items-center gap-1 text-sm">
            <li v-for="(crumb, index) in navigation.breadcrumbs" :key="index" class="flex items-center font-semibold">
                <component
                    :is="isBreadcrumbLink(index, navigation.breadcrumbs.length, crumb.url) ? Link : 'span'"
                    :aria-current="isLastBreadcrumb(index, navigation.breadcrumbs.length) ? 'page' : undefined"
                    :class="getBreadcrumbClasses(index, navigation.breadcrumbs.length)"
                    :href="isBreadcrumbLink(index, navigation.breadcrumbs.length, crumb.url) ? crumb.url : undefined"
                >
                    {{ crumb.label }}
                </component>
                <span v-if="!isLastBreadcrumb(index, navigation.breadcrumbs.length)" class="mx-2 text-breadcrumb-link opacity-60"> / </span>
            </li>
        </ol>

        <BorderDivider class="mt-2 mb-4 pt-2" />

        <div class="flex items-center justify-between gap-4">
            <!-- Previous Post Link -->
            <component :is="navigation.prevPost ? Link : 'span'" :class="getNavLinkClasses(navigation.prevPost)" :href="navigation.prevPost?.url">
                <span class="text-lg">{{ ARROW_LEFT }}</span>
                <div v-if="navigation.prevPost" class="flex flex-col items-start">
                    <span class="text-xs opacity-75">{{ t('blog.post_nav.previous') }}</span>
                    <span class="font-medium">{{ navigation.prevPost.title }}</span>
                </div>
                <span v-else>{{ t('blog.post_nav.previous') }}</span>
            </component>

            <!-- Back to Blog Link -->
            <component
                :is="navigation.isLandingPage ? 'span' : Link"
                :class="getBackLinkClasses(!navigation.isLandingPage)"
                :href="navigation.isLandingPage ? undefined : navigation.landingUrl"
            >
                {{ t('blog.post_nav.back_to_blog') }}
            </component>

            <!-- Next Post Link -->
            <component :is="navigation.nextPost ? Link : 'span'" :class="getNavLinkClasses(navigation.nextPost)" :href="navigation.nextPost?.url">
                <div v-if="navigation.nextPost" class="flex flex-col items-end">
                    <span class="text-xs opacity-90">{{ t('blog.post_nav.next') }}</span>
                    <span class="font-medium">{{ navigation.nextPost.title }}</span>
                </div>
                <span v-else>{{ t('blog.post_nav.next') }}</span>
                <span class="text-lg">{{ ARROW_RIGHT }}</span>
            </component>
        </div>
    </nav>
</template>
