<script lang="ts" setup>
import InputError from '@/components/InputError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

import { useI18nNs } from '@/composables/useI18nNs';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type BreadcrumbItem } from '@/types';

const { t } = await useI18nNs(['password', 'common']);

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: t('settings.password.title'),
        href: '/settings/password',
    },
];

const passwordInput = ref<HTMLInputElement | null>(null);
const currentPasswordInput = ref<HTMLInputElement | null>(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: (errors: any) => {
            if (errors.password) {
                form.reset('password', 'password_confirmation');
                if (passwordInput.value instanceof HTMLInputElement) {
                    passwordInput.value.focus();
                }
            }

            if (errors.current_password) {
                form.reset('current_password');
                if (currentPasswordInput.value instanceof HTMLInputElement) {
                    currentPasswordInput.value.focus();
                }
            }
        },
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="t('settings.password.title')" />

        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall :description="t('settings.password.update_description')" :title="t('settings.password.update_title')" />

                <form class="space-y-6" @submit.prevent="updatePassword">
                    <div class="grid gap-2">
                        <Label for="current_password">{{ t('settings.password.current_password') }}</Label>
                        <Input
                            id="current_password"
                            ref="currentPasswordInput"
                            v-model="form.current_password"
                            :placeholder="t('settings.password.current_password')"
                            autocomplete="current-password"
                            class="mt-1 block w-full"
                            type="password"
                        />
                        <InputError :message="form.errors.current_password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password">{{ t('settings.password.new_password') }}</Label>
                        <Input
                            id="password"
                            ref="passwordInput"
                            v-model="form.password"
                            :placeholder="t('settings.password.new_password')"
                            autocomplete="new-password"
                            class="mt-1 block w-full"
                            type="password"
                        />
                        <InputError :message="form.errors.password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password_confirmation">{{ t('settings.password.confirm_password') }}</Label>
                        <Input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            :placeholder="t('settings.password.confirm_password')"
                            autocomplete="new-password"
                            class="mt-1 block w-full"
                            type="password"
                        />
                        <InputError :message="form.errors.password_confirmation" />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="form.processing">{{ t('settings.password.save_button') }}</Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600">{{ t('settings.password.saved_message') }}</p>
                        </Transition>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
