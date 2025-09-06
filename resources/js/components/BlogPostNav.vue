<script lang="ts" setup>
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

interface NavInfo {
    prevPost?: { title: string; slug: string; url: string } | null;
    nextPost?: { title: string; slug: string; url: string } | null;
    landingUrl: string;
    isLandingPage?: boolean;
}

defineProps<{
    navigation?: NavInfo;
}>();

const { t } = useI18n();
</script>

<template>
    <nav v-if="navigation" :aria-label="t('landing.blog.post_nav.aria')" class="mt-8 border-t border-gray-200 pt-6 dark:border-gray-700">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <Link
                    v-if="navigation.prevPost"
                    :href="navigation.prevPost.url"
                    class="inline-flex items-center gap-2 rounded-sm border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                >
                    <span class="text-lg">←</span>
                    <div class="flex flex-col items-start">
                        <span class="text-xs opacity-75">{{ t('landing.blog.post_nav.previous') }}</span>
                        <span class="font-medium">{{ navigation.prevPost.title }}</span>
                    </div>
                </Link>
                <span
                    v-else
                    class="inline-flex items-center gap-2 rounded-sm border border-gray-200 px-3 py-2 text-sm text-gray-400 dark:border-gray-700 dark:text-gray-600"
                >
                    <span class="text-lg">←</span>
                    <span>{{ t('landing.blog.post_nav.previous') }}</span>
                </span>
            </div>

            <Link
                v-if="!navigation.isLandingPage"
                :href="navigation.landingUrl"
                class="inline-flex items-center rounded-sm border border-teal-600 bg-teal-50 px-3 py-2 text-sm font-medium text-teal-900 hover:bg-teal-100 dark:bg-teal-900/30 dark:text-teal-300 dark:hover:bg-teal-800/40"
            >
                {{ t('landing.blog.post_nav.back_to_blog') }}
            </Link>
            <span
                v-else
                class="inline-flex items-center rounded-sm border border-gray-200 px-3 py-2 text-sm font-medium text-gray-400 dark:border-gray-700 dark:text-gray-600"
            >
                {{ t('landing.blog.post_nav.back_to_blog') }}
            </span>

            <div class="flex items-center gap-4">
                <Link
                    v-if="navigation.nextPost"
                    :href="navigation.nextPost.url"
                    class="inline-flex items-center gap-2 rounded-sm border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                >
                    <div class="flex flex-col items-end">
                        <span class="text-xs opacity-75">{{ t('landing.blog.post_nav.next') }}</span>
                        <span class="font-medium">{{ navigation.nextPost.title }}</span>
                    </div>
                    <span class="text-lg">→</span>
                </Link>
                <span
                    v-else
                    class="inline-flex items-center gap-2 rounded-sm border border-gray-200 px-3 py-2 text-sm text-gray-400 dark:border-gray-700 dark:text-gray-600"
                >
                    <span>{{ t('landing.blog.post_nav.next') }}</span>
                    <span class="text-lg">→</span>
                </span>
            </div>
        </div>
    </nav>
</template>
