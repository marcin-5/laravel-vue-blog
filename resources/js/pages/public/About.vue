<script lang="ts" setup>
import AppLogo from '@/components/AppLogo.vue';
import PublicHomeLayout from '@/layouts/PublicHomeLayout.vue';

defineProps<{
    aboutHeading: string;
    aboutHtml: string;
}>();
</script>

<template>
    <PublicHomeLayout>
        <div class="mb-8 text-center text-foreground">
            <AppLogo :size="'md'" />
        </div>
        <h2 class="mb-4 font-header text-3xl font-semibold text-shadow-stone-700 dark:text-shadow-stone-50" v-html="aboutHeading"></h2>
        <div class="prose max-w-none text-shadow-stone-800 dark:text-shadow-stone-100" v-html="aboutHtml"></div>
    </PublicHomeLayout>
</template>

<style scoped>
.prose :deep(p) {
    margin: 0.5rem 0;
}
</style>
