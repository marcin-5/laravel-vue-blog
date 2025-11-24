<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { useI18nNs } from '@/composables/useI18nNs';
import { onMounted, ref } from 'vue';

const { t } = await useI18nNs('common');
const show = ref(false);

onMounted(() => {
    const accepted = localStorage.getItem('cookie_consent_accepted');
    if (!accepted) {
        show.value = true;
    }
});

const accept = () => {
    localStorage.setItem('cookie_consent_accepted', 'true');
    show.value = false;
};
</script>

<template>
    <div
        v-if="show"
        class="fixed right-0 bottom-0 left-0 z-50 flex flex-col items-center justify-between gap-4 border-t border-border bg-warning p-4 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)] sm:flex-row md:px-8"
    >
        <p class="text-sm text-warning-foreground">
            {{ t('common.cookie_notice.message') }}
        </p>
        <Button variant="exit" @click="accept">
            {{ t('common.cookie_notice.button') }}
        </Button>
    </div>
</template>
