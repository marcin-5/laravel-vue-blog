<script lang="ts" setup>
import { Head } from '@inertiajs/vue3';

interface Props {
    title: string | undefined | null;
    description: string | undefined | null;
    canonicalUrl: string | undefined | null;
    ogImage?: string | null;
    locale?: string | null;
    ogType?: string | null; // e.g., website, blog, article
    publishedTime?: string | null;
    modifiedTime?: string | null;
    structuredData?: Record<string, any> | null;
    alternateLinks?: Array<{ hreflang: string; href: string }> | null;
}

defineProps<Props>();
</script>

<template>
    <Head :title="title || ''">
        <!-- Primary Meta Tags -->
        <meta :content="description || ''" name="description" />
        <meta content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" name="robots" />
        <link v-if="canonicalUrl" :href="canonicalUrl" rel="canonical" />

        <template v-for="link in alternateLinks" :key="link.hreflang">
            <link :href="link.href" :hreflang="link.hreflang" rel="alternate" />
        </template>

        <meta v-if="locale" :content="locale" http-equiv="content-language" />

        <!-- Open Graph / Facebook -->
        <meta :content="ogType || 'website'" property="og:type" />
        <meta :content="title || ''" property="og:title" />
        <meta :content="description || ''" property="og:description" />
        <meta v-if="canonicalUrl" :content="canonicalUrl" property="og:url" />
        <meta v-if="ogImage" :content="ogImage" property="og:image" />
        <meta content="1200" property="og:image:width" />
        <meta content="630" property="og:image:height" />
        <meta :content="locale || 'en'" property="og:locale" />
        <meta v-if="publishedTime" :content="publishedTime" property="article:published_time" />
        <meta v-if="modifiedTime" :content="modifiedTime" property="article:modified_time" />

        <!-- Twitter -->
        <meta content="summary_large_image" name="twitter:card" />
        <meta :content="title || ''" name="twitter:title" />
        <meta :content="description || ''" name="twitter:description" />
        <meta v-if="ogImage" :content="ogImage" name="twitter:image" />

        <!-- Structured Data intentionally omitted in client templates to avoid side effects; SSR injects JSON-LD in app.blade.php -->
    </Head>
</template>
