<script lang="ts" setup>
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

import { useI18nGate } from '@/composables/useI18nGate';

const { t } = await useI18nGate('auth');

defineProps<{
    status?: string;
}>();

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};
</script>

<template>
    <AuthLayout :description="t('auth.verify_email.description')" :title="t('auth.verify_email.title')">
        <Head :title="t('auth.verify_email.title')" />

        <div v-if="status === 'verification-link-sent'" class="mb-4 text-center text-sm font-medium text-green-600">
            {{ t('auth.verify_email.verification_link_sent') }}
        </div>

        <form class="space-y-6 text-center" @submit.prevent="submit">
            <Button :disabled="form.processing" variant="secondary">
                <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                {{ t('auth.verify_email.resend') }}
            </Button>

            <TextLink :href="route('logout')" as="button" class="mx-auto block text-sm" method="post"> {{ t('auth.verify_email.logout') }} </TextLink>
        </form>
    </AuthLayout>
</template>
