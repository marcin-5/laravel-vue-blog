<script lang="ts" setup>
import AppLogo from '@/components/AppLogo.vue';
import PublicNavbar from '@/components/PublicNavbar.vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
</script>

<template>
    <div class="flex min-h-screen flex-col">
        <PublicNavbar />
        <main class="mx-auto w-full max-w-[1024px] p-6 lg:p-8">
            <div class="mb-8 text-center text-foreground">
                <AppLogo :size="'md'" />
            </div>
            <h2 class="mb-4 font-header text-3xl font-semibold text-shadow-stone-700 dark:text-shadow-stone-50">{{ t('about.heading', 'About') }}</h2>
            <p class="prose max-w-none text-shadow-stone-800 dark:text-shadow-stone-100" v-html="t('about.content')"></p>
        </main>
    </div>
</template>

<style scoped>
.prose :deep(p) {
    margin: 0.5rem 0;
}
</style>
