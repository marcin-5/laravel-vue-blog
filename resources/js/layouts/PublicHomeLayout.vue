<script lang="ts" setup>
import PublicNavbar from '@/components/PublicNavbar.vue';
import HeadSEO from '@/components/HeadSEO.vue';
import { Toaster } from '@/components/ui/toast';

defineProps<{
    maxWidth?: string;
}>();
</script>

<template>
    <HeadSEO />
    <div class="flex min-h-screen flex-col">
        <PublicNavbar />
        <main :class="['mx-auto flex w-full flex-1 flex-col p-6 lg:p-8', maxWidth || 'max-w-7xl']">
            <slot />
        </main>
    </div>
    <Toaster />
</template>
