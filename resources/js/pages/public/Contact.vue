<script lang="ts" setup>
import ContactForm from '@/components/ContactForm.vue';
import PublicHomeLayout from '@/layouts/PublicHomeLayout.vue';
import { useI18n } from 'vue-i18n';

defineProps<{
    recipientName?: string;
}>();

const { t } = useI18n();
</script>

<template>
    <PublicHomeLayout maxWidth="max-w-[768px]">
        <h1 class="mb-6 font-serif text-3xl font-semibold text-foreground">
            {{ t('contact.heading', 'Contact') }}
        </h1>
        <ContactForm :recipientName="recipientName" :submitRoute="route('public.contact.submit')" />
    </PublicHomeLayout>
</template>
