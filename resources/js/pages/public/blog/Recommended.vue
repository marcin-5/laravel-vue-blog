<script lang="ts" setup>
import BlogHeader from '@/components/blog/BlogHeader.vue';
import PublicBlogShell from '@/components/blog/PublicBlogShell.vue';
import type { PublicBlogRecommendedLinksPageProps } from '@/types/blog.types';
import { useI18n } from 'vue-i18n';
import PostBackLink from '@/components/blog/PostBackLink.vue';

const props = defineProps<PublicBlogRecommendedLinksPageProps>();

const { t } = useI18n();
</script>

<template>
    <PublicBlogShell :blog="blog" :chrome="chrome" :sidebar="0">
        <template #header>
            <BlogHeader :blog="blog" />
        </template>

        <template #content>
            <section aria-labelledby="recommended-links-heading">
                <h2 id="recommended-links-heading" class="mb-6 text-2xl font-semibold text-foreground">
                    {{ t('blog.recommended_links.heading') }}
                </h2>

                <ul v-if="props.recommendedLinks.length > 0" class="mb-8 space-y-6">
                    <li v-for="link in props.recommendedLinks" :key="link.id">
                        <a :href="link.url" class="font-medium break-all text-link hover:text-link-hover" rel="noopener noreferrer" target="_blank">
                            {{ link.name || link.url }}
                        </a>
                        <p v-if="link.description" class="mt-2 whitespace-pre-wrap">{{ link.description }}</p>
                    </li>
                </ul>

                <section v-for="category in props.recommendedLinkCategories" :key="category.id" class="mb-8">
                    <h3 class="mb-4 text-xl font-semibold text-foreground">{{ category.name }}</h3>
                    <ul class="space-y-6">
                        <li v-for="link in category.recommendedLinks" :key="link.id">
                            <a
                                :href="link.url"
                                class="font-medium break-all text-link hover:text-link-hover"
                                rel="noopener noreferrer"
                                target="_blank"
                            >
                                {{ link.name || link.url }}
                            </a>
                            <p v-if="link.description" class="mt-2 whitespace-pre-wrap">{{ link.description }}</p>
                        </li>
                    </ul>
                </section>
            </section>
        </template>

        <template #post-navigation>
            <PostBackLink class="mb-2" />
        </template>
    </PublicBlogShell>
</template>
