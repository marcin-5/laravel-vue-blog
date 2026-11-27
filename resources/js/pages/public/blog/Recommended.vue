<script lang="ts" setup>
import BlogHeader from '@/components/blog/BlogHeader.vue';
import PublicBlogShell from '@/components/blog/PublicBlogShell.vue';
import type { PublicBlogRecommendedLinksPageProps } from '@/types/blog.types';
import { useI18n } from 'vue-i18n';
import PostBackLink from '@/components/blog/PostBackLink.vue';

defineProps<PublicBlogRecommendedLinksPageProps>();

const { t } = useI18n();
</script>

<template>
    <PublicBlogShell :blog="blog" :chrome="chrome" :sidebar="0">
        <template #header>
            <BlogHeader :blog="blog" />
        </template>

        <template #content>
            <section aria-labelledby="recommended-links-heading">
                <h1 id="recommended-links-heading" class="mb-6 text-2xl font-semibold text-foreground">
                    {{ t('blog.recommended_links.heading') }}
                </h1>

                <ul v-if="recommendedLinks.length > 0" class="space-y-6">
                    <li v-for="link in recommendedLinks" :key="link.id">
                        <a :href="link.url" class="font-medium break-all text-link hover:text-link-hover" rel="noopener noreferrer" target="_blank">
                            {{ link.name || link.url }}
                        </a>
                        <p v-if="link.description" class="mt-2 whitespace-pre-wrap">{{ link.description }}</p>
                    </li>
                </ul>
            </section>
        </template>

        <template #post-navigation>
            <PostBackLink class="mb-2" />
        </template>
    </PublicBlogShell>
</template>
