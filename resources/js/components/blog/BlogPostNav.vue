<script lang="ts" setup>
import BorderDivider from '@/components/blog/BorderDivider.vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

interface PostLink {
    title: string;
    slug: string;
    url: string;
}

interface NavInfo {
    prevPost?: PostLink | null;
    nextPost?: PostLink | null;
    landingUrl: string;
    isLandingPage?: boolean;
}

defineProps<{
    navigation?: NavInfo;
}>();
const { t } = useI18n();

const BASE_LINK_CLASSES = 'inline-flex items-center rounded-sm px-3 py-2 text-sm';
const ACTIVE_LINK_CLASSES = 'border border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800';
const INACTIVE_LINK_CLASSES = 'border border-gray-200 dark:border-gray-700 dark:text-gray-600 text-gray-500';

const navLinkClasses = (postExists: boolean) => [`${BASE_LINK_CLASSES} gap-2`, postExists ? ACTIVE_LINK_CLASSES : INACTIVE_LINK_CLASSES];
const backLinkClasses = (isLink: boolean) => [`${BASE_LINK_CLASSES} font-medium`, isLink ? ACTIVE_LINK_CLASSES : INACTIVE_LINK_CLASSES];
</script>

<template>
    <nav v-if="navigation" :aria-label="t('blog.post_nav.aria')">
        <BorderDivider class="my-4 pt-2" />
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
