<script lang="ts" setup>
import BlogHeader from '@/components/blog/BlogHeader.vue';
import PublicBlogShell from '@/components/blog/PublicBlogShell.vue';
import ContactForm from '@/components/ContactForm.vue';
import type { Blog, BlogChrome } from '@/types/blog.types';
import { SEO } from '@/types';
import { useI18n } from 'vue-i18n';

defineProps<{
    blog: Blog;
    chrome: BlogChrome;
    seo?: SEO;
    recipientName?: string;
    submitUrl: string;
}>();

const { t } = useI18n();
</script>

<template>
    <PublicBlogShell :blog="blog" :chrome="chrome">
        <template #header>
            <BlogHeader :blog="blog" />
        </template>

        <template #content>
            <h1 class="mb-6 font-serif text-3xl font-semibold">
                {{ t('contact.heading', 'Contact') }}
            </h1>
            <ContactForm :recipientName="recipientName" :submitRoute="submitUrl" />
        </template>
    </PublicBlogShell>
</template>
