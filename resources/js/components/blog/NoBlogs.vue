<script lang="ts" setup>
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
</script>

<template>
    <div class="mt-20 flex flex-col items-center justify-center gap-4 text-center">
        <h1 class="text-xl font-semibold text-amber-800 dark:text-amber-50">{{ t('no_blogs.welcome') }}</h1>
        <h2 class="mb-8 text-lg text-orange-800 dark:text-orange-50">{{ t('no_blogs.nice_to_see_you') }}</h2>

        <p class="text-base text-orange-900 dark:text-orange-100">{{ t('no_blogs.no_blogs_yet') }}</p>
        <p class="text-base text-orange-900 dark:text-orange-100">{{ t('no_blogs.will_change_soon') }}</p>

        <p class="mt-8 text-orange-950 dark:text-amber-100">{{ t('no_blogs.invite_soon') }}</p>
    </div>
</template>

<style scoped></style>
