<script lang="ts" setup>
import BorderDivider from '@/components/blog/BorderDivider.vue';
import type { Navigation, NavPost } from '@/types/blog.types';
import { Link } from '@inertiajs/vue3';
import clsx from 'clsx';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    navigation?: Navigation;
}>();

const { t } = useI18n();

const LINK_STYLES = {
    base: 'inline-flex items-center rounded-sm px-3 py-2 text-sm transition-colors bg-card',
    active: 'border border-border text-primary hover:bg-secondary',
    inactive: 'border border-border text-primary opacity-50 cursor-default',
} as const;

const buildLinkClasses = (isActive: boolean, ...extra: string[]) =>
    clsx(LINK_STYLES.base, ...extra, isActive ? LINK_STYLES.active : LINK_STYLES.inactive);

const getNavLinkClasses = (post: NavPost | null | undefined) => buildLinkClasses(!!post, 'gap-2');

const getBackLinkClasses = (isClickable: boolean) => buildLinkClasses(isClickable, 'font-medium');

// Breadcrumb helpers
const breadcrumbs = computed(() => props.navigation?.breadcrumbs ?? []);
const breadcrumbCount = computed(() => breadcrumbs.value.length);

const isLastBreadcrumb = (index: number) => index === breadcrumbCount.value - 1;
const isBreadcrumbLink = (index: number, url?: string | null) => !isLastBreadcrumb(index) && !!url;
const getBreadcrumbClasses = (index: number) =>
    clsx('hover:underline', isLastBreadcrumb(index) ? 'text-breadcrumb-link-active' : 'text-breadcrumb-link');
</script>

<template>
    <nav v-if="navigation" :aria-label="t('blog.post_nav.aria')" :style="{ fontFamily: 'var(--blog-nav-font)' }">
        <BorderDivider class="my-4 pt-2" />

        <!-- Breadcrumbs -->
        <ol v-if="breadcrumbs.length" aria-label="Breadcrumb" class="flex flex-wrap items-center gap-1 text-sm">
            <li v-for="(crumb, index) in breadcrumbs" :key="index" class="flex items-center font-semibold">
                <component
                    :is="isBreadcrumbLink(index, crumb.url) ? Link : 'span'"
                    :aria-current="isLastBreadcrumb(index) ? 'page' : undefined"
                    :class="getBreadcrumbClasses(index)"
                    :href="isBreadcrumbLink(index, crumb.url) ? crumb.url : undefined"
                >
                    {{ crumb.label }}
                </component>
                <span v-if="!isLastBreadcrumb(index)" class="mx-2 text-breadcrumb-link opacity-60"> / </span>
            </li>
        </ol>

        <BorderDivider class="mt-2 mb-4 pt-2" />

        <div class="flex items-center justify-between gap-4">
            <!-- Previous Post Link -->
            <component :is="navigation.prevPost ? Link : 'span'" :class="getNavLinkClasses(navigation.prevPost)" :href="navigation.prevPost?.url">
                <span class="text-lg">←</span>
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
                {{ t(navigation.isGroup ? 'blog.post_nav.back_to_group' : 'blog.post_nav.back_to_blog') }}
            </component>

            <!-- Next Post Link -->
            <component :is="navigation.nextPost ? Link : 'span'" :class="getNavLinkClasses(navigation.nextPost)" :href="navigation.nextPost?.url">
                <div v-if="navigation.nextPost" class="flex flex-col items-end">
                    <span class="text-xs opacity-90">{{ t('blog.post_nav.next') }}</span>
                    <span class="font-medium">{{ navigation.nextPost.title }}</span>
                </div>
                <span v-else>{{ t('blog.post_nav.next') }}</span>
                <span class="text-lg">→</span>
            </component>
        </div>
    </nav>
</template>
