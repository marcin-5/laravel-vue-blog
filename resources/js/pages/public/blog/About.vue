<script lang="ts" setup>
import BlogBreadcrumbs from '@/components/blog/BlogBreadcrumbs.vue';
import BlogFooter from '@/components/blog/BlogFooter.vue';
import BlogHeader from '@/components/blog/BlogHeader.vue';
import BlogLayout from '@/components/blog/BlogLayout.vue';
import BorderDivider from '@/components/blog/BorderDivider.vue';
import type { Blog, Navigation, Tag } from '@/types/blog.types';
import { handleContentClick } from '@/utils/domUtils';
import { hasContent, selectRandomMotto } from '@/utils/stringUtils';
import { computed } from 'vue';
import { SEO } from '@/types';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    blog: Blog;
    footerHtml?: string;
    sidebar?: number;
    navigation?: Navigation;
    locale?: string;
    seo?: SEO;
    allTags?: Tag[];
}>();

const { t } = useI18n();

const hasAboutContent = computed(() => hasContent(props.blog.aboutHtml));
const hasFooterContent = computed(() => hasContent(props.footerHtml));
const displayedMotto = selectRandomMotto(props.blog.motto);

</script>

<template>
    <BlogLayout v-if="blog" :isPublic="true" :sidebar="sidebar" :theme="blog.theme">
        <template #top-divider>
            <BorderDivider class="mb-4" />
        </template>

        <template #header>
            <BlogHeader :blog="blog" :displayedMotto="displayedMotto" />
        </template>

        <template #content>
            <div v-if="hasAboutContent" class="prose max-w-none text-primary" @click="handleContentClick" v-html="blog.aboutHtml" />
            <div v-else class="text-center py-12 text-muted-foreground italic">
                {{ t('about.empty') }}
            </div>
        </template>

        <template #middle-divider>
            <BorderDivider class="my-4" />
        </template>

        <template #breadcrumbs>
            <BlogBreadcrumbs :breadcrumbs="navigation?.breadcrumbs ?? []" />
        </template>

        <template #footer>
            <template v-if="hasFooterContent">
                <BorderDivider class="my-4" />
                <BlogFooter :html="footerHtml || ''" />
            </template>
        </template>
    </BlogLayout>
</template>
