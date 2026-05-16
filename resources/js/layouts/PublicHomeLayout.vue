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
        <main :class="['mx-auto w-full p-6 lg:p-8', maxWidth || 'max-w-[1024px]']">
            <slot />
        </main>
    </div>
    <Toaster />
</template>
