<script lang="ts" setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { SEO } from '@/types';

const DEFAULT_TITLE = 'Osobliwy Blog';
const DEFAULT_DESCRIPTION = 'Default meta description';
const DEFAULT_ROBOTS = 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
const DEFAULT_LOCALE = 'en';

const page = usePage();

const pageSeo = computed(() => page.props.seo as SEO | undefined);

const seoDefaults = computed(() => ({
    title: pageSeo.value?.title ?? DEFAULT_TITLE,
    description: pageSeo.value?.description ?? DEFAULT_DESCRIPTION,
    robots: pageSeo.value?.robots ?? DEFAULT_ROBOTS,
    locale: pageSeo.value?.locale ?? DEFAULT_LOCALE,
}));

const serializeStructuredData = (structuredData: SEO['structuredData'] | undefined): string => {
    return structuredData ? JSON.stringify(structuredData) : '';
};

const structuredDataJson = computed(() => serializeStructuredData(pageSeo.value?.structuredData));
</script>

<template>
    <Head>
        <title>{{ seoDefaults.title }}</title>
        <meta name="description" :content="seoDefaults.description" />
        <meta name="robots" :content="seoDefaults.robots" />
        <link v-if="pageSeo?.canonicalUrl" :href="pageSeo.canonicalUrl" rel="canonical" />
        <meta :content="seoDefaults.locale" http-equiv="content-language" />

        <meta property="og:type" :content="pageSeo?.ogType || 'website'" />
        <meta property="og:title" :content="seoDefaults.title" />
        <meta property="og:description" :content="seoDefaults.description" />
        <meta property="og:url" :content="pageSeo?.canonicalUrl" />
        <meta property="og:image" :content="pageSeo?.ogImage" />
        <meta property="og:image:width" content="1024" />
        <meta property="og:image:height" content="1024" />
        <meta property="og:locale" :content="seoDefaults.locale" />
        <meta v-if="pageSeo?.publishedTime" property="article:published_time" :content="pageSeo.publishedTime" />
        <meta v-if="pageSeo?.modifiedTime" property="article:modified_time" :content="pageSeo.modifiedTime" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="seoDefaults.title" />
        <meta name="twitter:description" :content="seoDefaults.description" />
        <meta name="twitter:image" :content="pageSeo?.ogImage" />

        <template v-if="pageSeo?.alternateLinks">
            <link v-for="link in pageSeo.alternateLinks" :key="link.hreflang" rel="alternate" :href="link.href" :hreflang="link.hreflang" />
        </template>

        <component :is="'script'" v-if="structuredDataJson" type="application/ld+json">
            {{ structuredDataJson }}
        </component>
    </Head>
</template>
