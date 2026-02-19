<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const show = ref(false);

const COOKIE_NAME = 'cookie_consent';
const COOKIE_MAX_AGE = 60 * 60 * 24 * 365; // 1 year in seconds

const getCookie = (name: string): string | null => {
    if (typeof document === 'undefined') {
        return null;
    }

    const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
    return match ? match[2] : null;
};

const setCookie = (name: string, value: string, maxAge: number): void => {
    if (typeof document === 'undefined') {
        return;
    }

    document.cookie = `${name}=${value}; path=/; max-age=${maxAge}; SameSite=Strict`;
};

onMounted(() => {
    const consent = getCookie(COOKIE_NAME);
    if (!consent) {
        show.value = true;
    }
});

const accept = () => {
    setCookie(COOKIE_NAME, 'accepted', COOKIE_MAX_AGE);
    show.value = false;
};

const reject = () => {
    setCookie(COOKIE_NAME, 'rejected', COOKIE_MAX_AGE);
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
        <div class="flex gap-2">
            <Button variant="outline" @click="reject">
                {{ t('common.cookie_notice.reject_button') }}
            </Button>
            <Button variant="constructive" @click="accept">
                {{ t('common.cookie_notice.accept_button') }}
            </Button>
        </div>
    </div>
</template>
