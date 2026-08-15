<script lang="ts" setup>
import BlogHeader from '@/components/blog/BlogHeader.vue';
import PublicBlogShell from '@/components/blog/PublicBlogShell.vue';
import type { Blog, BlogChrome } from '@/types/blog.types';
import { handleContentClick } from '@/utils/domUtils';
import { hasContent } from '@/utils/stringUtils';
import { computed } from 'vue';
import { SEO } from '@/types';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    blog: Blog;
    chrome: BlogChrome;
    seo?: SEO;
}>();

const { t } = useI18n();

const hasAboutContent = computed(() => hasContent(props.blog.aboutHtml));
</script>

<template>
    <PublicBlogShell :blog="blog" :chrome="chrome">
        <template #header>
            <BlogHeader :blog="blog" />
        </template>

        <template #content>
            <div v-if="hasAboutContent" class="prose max-w-none text-primary" @click="handleContentClick" v-html="blog.aboutHtml" />
            <div v-else class="py-12 text-center text-muted-foreground italic">
                {{ t('about.empty') }}
            </div>
        </template>
    </PublicBlogShell>
</template>
