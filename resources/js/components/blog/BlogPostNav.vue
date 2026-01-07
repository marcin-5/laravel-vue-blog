<script lang="ts" setup>
import BorderDivider from '@/components/blog/BorderDivider.vue';
import type { Navigation } from '@/types/blog.types';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

defineProps<{
    navigation?: Navigation;
}>();

const { t } = useI18n();

const BASE_LINK_CLASSES = 'inline-flex items-center rounded-sm px-3 py-2 text-sm';
const ACTIVE_LINK_CLASSES = 'border border-border text-foreground hover:bg-muted';
const INACTIVE_LINK_CLASSES = 'border border-border text-muted-foreground';

const navLinkClasses = (postExists: boolean) => [`${BASE_LINK_CLASSES} gap-2`, postExists ? ACTIVE_LINK_CLASSES : INACTIVE_LINK_CLASSES];
const backLinkClasses = (isLink: boolean) => [`${BASE_LINK_CLASSES} font-medium`, isLink ? ACTIVE_LINK_CLASSES : INACTIVE_LINK_CLASSES];
</script>

<template>
    <nav v-if="navigation" :aria-label="t('blog.post_nav.aria')" :style="{ fontFamily: 'var(--blog-navbar-font)' }">
        <BorderDivider class="my-4 pt-2" />

        <!-- Breadcrumbs -->
        <ol v-if="navigation.breadcrumbs && navigation.breadcrumbs.length" aria-label="Breadcrumb" class="flex flex-wrap items-center gap-1 text-sm">
            <li v-for="(crumb, index) in navigation.breadcrumbs" :key="index" class="flex items-center font-semibold">
                <component
                    :is="index < navigation.breadcrumbs.length - 1 && crumb.url ? Link : 'span'"
                    :aria-current="index === navigation.breadcrumbs.length - 1 ? 'page' : undefined"
                    :class="[
                        {
                            'text-breadcrumb-link-active': index === navigation.breadcrumbs.length - 1,
                            'text-breadcrumb-link': index < navigation.breadcrumbs.length - 1,
                        },
                        'hover:underline',
                    ]"
                    :href="index < navigation.breadcrumbs.length - 1 ? crumb.url || undefined : undefined"
                >
                    {{ crumb.label }}
                </component>
                <span v-if="index < navigation.breadcrumbs.length - 1" class="mx-2 text-breadcrumb-link opacity-60">/</span>
            </li>
        </ol>

        <BorderDivider class="mt-2 mb-4 pt-2" />

        <div class="flex items-center justify-between gap-4">
            <component :is="navigation.prevPost ? Link : 'span'" :class="navLinkClasses(!!navigation.prevPost)" :href="navigation.prevPost?.url">
                <span class="text-lg">←</span>
                <div v-if="navigation.prevPost" class="flex flex-col items-start">
                    <span class="text-xs opacity-75">{{ t('blog.post_nav.previous') }}</span>
                    <span class="font-medium">{{ navigation.prevPost.title }}</span>
                </div>
                <span v-else>{{ t('blog.post_nav.previous') }}</span>
            </component>

            <component
                :is="!navigation.isLandingPage ? Link : 'span'"
                :class="backLinkClasses(!navigation.isLandingPage)"
                :href="!navigation.isLandingPage ? navigation.landingUrl : undefined"
            >
                {{ t('blog.post_nav.back_to_blog') }}
            </component>

            <component :is="navigation.nextPost ? Link : 'span'" :class="navLinkClasses(!!navigation.nextPost)" :href="navigation.nextPost?.url">
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
