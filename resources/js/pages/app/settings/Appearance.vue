<script lang="ts" setup>
import { Head, router } from '@inertiajs/vue3';

import AppearanceTabs from '@/components/AppearanceTabs.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { type BreadcrumbItem } from '@/types';

import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

import { useI18nNs } from '@/composables/useI18nNs';
import { setLocale as setI18nLocale } from '@/i18n';

const { t } = await useI18nNs(['appearance', 'common']);

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: t('settings.appearance.title'),
        href: '/settings/appearance',
    },
];

function onLocaleChange(newLocale: string) {
    router.post(
        '/locale',
        { locale: newLocale },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: async () => {
                await setI18nLocale(newLocale);
                // useI18nNs will handle ensuring namespace for the new locale via its watch
            },
        },
    );
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="t('settings.appearance.title')" />

        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall :description="t('settings.appearance.description')" :title="t('settings.appearance.title')" />
                <AppearanceTabs />
                <div class="mt-6">
                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300" for="locale">{{
                        t('appearance.language')
                    }}</label>
                    <select
                        id="locale"
                        class="mt-1 block w-48 rounded-md border-neutral-300 bg-white py-1.5 pr-10 pl-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:outline-none dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100"
                        @change="onLocaleChange(($event.target as HTMLSelectElement).value)"
                    >
                        <option :selected="$page.props.locale === 'en'" value="en">English</option>
                        <option :selected="$page.props.locale === 'pl'" value="pl">Polski</option>
                    </select>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
