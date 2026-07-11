<script lang="ts" setup>
import BlogBreadcrumbs from '@/components/blog/BlogBreadcrumbs.vue';
import BlogFooter from '@/components/blog/BlogFooter.vue';
import BlogHeader from '@/components/blog/BlogHeader.vue';
import BlogLayout from '@/components/blog/BlogLayout.vue';
import BorderDivider from '@/components/blog/BorderDivider.vue';
import ContactForm from '@/components/ContactForm.vue';
import type { Blog, Navigation, Tag } from '@/types/blog.types';
import { selectRandomMotto } from '@/utils/stringUtils';
import { hasContent } from '@/utils/stringUtils';
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
    recipientName?: string;
    submitUrl: string;
}>();

const { t } = useI18n();

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
            <h1 class="mb-6 font-serif text-3xl font-semibold">
                {{ t('contact.heading', 'Contact') }}
            </h1>
            <ContactForm :recipientName="recipientName" :submitRoute="submitUrl" />
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
