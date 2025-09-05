<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AppearanceTabs from '@/components/AppearanceTabs.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { type BreadcrumbItem } from '@/types';

import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

import { useI18n } from 'vue-i18n';
const { t, locale } = useI18n();
import { ensureNamespace, setLocale as setI18nLocale } from '@/i18n';
await ensureNamespace(locale.value, 'appearance');
const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: t('settings.appearance.title'),
        href: '/settings/appearance',
    },
];
import { router } from '@inertiajs/vue3';

function onLocaleChange(newLocale: string) {
    router.post('/locale', { locale: newLocale }, {
        preserveScroll: true,
        onSuccess: async () => {
            await setI18nLocale(newLocale);
            await ensureNamespace(newLocale, 'appearance');
        },
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="$t('settings.appearance.title')" />

        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall :title="$t('settings.appearance.title')" :description="$t('settings.appearance.description')" />
                <AppearanceTabs />
                <div class="mt-6">
                    <label for="locale" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">{{ $t('settings.appearance.title') }} – {{ $t('appearance.system') }}</label>
                    <select id="locale" class="mt-1 block w-48 rounded-md border-neutral-300 bg-white py-1.5 pl-3 pr-10 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100" @change="onLocaleChange(($event.target as HTMLSelectElement).value)">
                        <option value="en" :selected="$page.props.locale === 'en'">English</option>
                        <option value="pl" :selected="$page.props.locale === 'pl'">Polski</option>
                    </select>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
