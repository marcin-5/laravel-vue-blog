<script lang="ts" setup>
import AppLogo from '@/components/AppLogo.vue';
import PublicNavbar from '@/components/PublicNavbar.vue';
import SeoHead from '@/components/seo/SeoHead.vue';
import { SEO } from '@/types/blog';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    locale?: string | null;
    seo?: Partial<SEO> | null;
}>();

const { t } = useI18n();

const getSeoProperty = <T,>(key: keyof SEO, fallback: T): T => {
    return (props.seo?.[key] as T) ?? fallback;
};

const title = computed(() => getSeoProperty('title', t('about.meta.title', 'About')));
const description = computed(() => getSeoProperty('description', t('about.meta.description', 'About this site')));
const canonicalUrl = computed(() => getSeoProperty('canonicalUrl', ''));
const ogImage = computed(() => getSeoProperty('ogImage', null));
const ogType = computed(() => getSeoProperty('ogType', 'website'));
const locale = computed(() => getSeoProperty('locale', props.locale ?? 'en'));
const structuredData = computed(() => getSeoProperty('structuredData', null));
</script>

<template>
    <SeoHead
        :canonical-url="canonicalUrl"
        :description="description"
        :locale="locale"
        :og-image="ogImage"
        :og-type="ogType"
        :structured-data="structuredData"
        :title="title"
    />
    <div class="flex min-h-screen flex-col">
        <PublicNavbar />
        <main class="mx-auto w-full max-w-[1024px] p-6 lg:p-8">
            <div class="mb-8 text-center text-primary">
                <AppLogo :size="'md'" />
            </div>
            <h2 class="mb-4 font-serif text-3xl font-semibold text-shadow-stone-700 dark:text-shadow-stone-50">{{ t('about.heading', 'About') }}</h2>
            <p class="prose max-w-none text-shadow-stone-800 dark:text-shadow-stone-100" v-html="t('about.content')"></p>
        </main>
    </div>
</template>

<style scoped>
.prose :deep(p) {
    margin: 0.5rem 0;
}
</style>
