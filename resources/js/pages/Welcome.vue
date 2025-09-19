<script lang="ts" setup>
import PublicNavbar from '@/components/PublicNavbar.vue';
import { ensureNamespace } from '@/i18n';
import { Head } from '@inertiajs/vue3';
import { onMounted, onServerPrefetch } from 'vue';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();

// Load namespace in SSR and on client without top-level await
const loadNs = async () => {
    try {
        await ensureNamespace(locale.value, 'landing');
    } catch (error) {
        console.warn('Failed to load landing namespace:', error);
    }
};

onServerPrefetch(loadNs);
onMounted(loadNs);
</script>

<template>
    <Head :title="t('landing.meta.welcomeTitle', 'Welcome')">
        <meta :content="t('landing.meta.welcomeDescription', 'Welcome to Laravel Blog')" name="description" />
    </Head>
    <div class="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a]">
        <PublicNavbar />
        <div class="mx-auto w-full max-w-[1024px] p-6 lg:p-8">
            <h1 class="text-4xl font-bold text-slate-800 dark:text-slate-200">Welcome!</h1>
        </div>
    </div>
</template>
