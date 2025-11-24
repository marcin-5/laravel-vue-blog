<script lang="ts" setup>
import CookieConsent from '@/components/CookieConsent.vue';
import AuthLayout from '@/layouts/auth/AuthSimpleLayout.vue';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <AuthLayout :description="description" :title="title">
        <slot />
    </AuthLayout>
    <CookieConsent />
</template>
