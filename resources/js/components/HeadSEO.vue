<script lang="ts" setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { SEO } from '@/types';

const page = usePage();
const seo = computed(() => page.props.seo as SEO | undefined);
const structuredDataJson = computed(() => (seo.value?.structuredData ? JSON.stringify(seo.value.structuredData) : ''));
</script>

<template>
    <Head v-if="seo">
        <title>{{ seo.title }}</title>
        <meta :content="seo.description" name="description" />
        <link :href="seo.canonicalUrl" rel="canonical" />

        <meta :content="seo.ogType" property="og:type" />
        <meta :content="seo.title" property="og:title" />
        <meta :content="seo.description" property="og:description" />
        <meta :content="seo.canonicalUrl" property="og:url" />
        <meta :content="seo.ogImage" property="og:image" />
        <meta :content="seo.locale" property="og:locale" />

        <meta v-if="seo.publishedTime" :content="seo.publishedTime" property="article:published_time" />
        <meta v-if="seo.modifiedTime" :content="seo.modifiedTime" property="article:modified_time" />

        <meta content="summary_large_image" name="twitter:card" />
        <meta :content="seo.title" name="twitter:title" />
        <meta :content="seo.description" name="twitter:description" />
        <meta :content="seo.ogImage" name="twitter:image" />

        <component :is="'script'" v-if="structuredDataJson" type="application/ld+json">
            {{ structuredDataJson }}
        </component>

        <template v-if="seo.alternateLinks">
            <link v-for="link in seo.alternateLinks" :key="link.hreflang" :href="link.href" :hreflang="link.hreflang" rel="alternate" />
        </template>
    </Head>
</template>
