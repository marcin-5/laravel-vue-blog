<script lang="ts" setup>
import { Head, router } from '@inertiajs/vue3';

import AppearanceTabs from '@/components/AppearanceTabs.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { type BreadcrumbItem } from '@/types';

import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { setLocale as setI18nLocale } from '@/i18n';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

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
                    <Select :model-value="$page.props.locale as string" @update:model-value="onLocaleChange">
                        <SelectTrigger id="locale" class="mt-1 h-10 w-48">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="en">{{ t('appearance.en') }}</SelectItem>
                            <SelectItem value="pl">{{ t('appearance.pl') }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
