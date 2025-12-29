<script lang="ts" setup>
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

import { useI18nGate } from '@/composables/useI18nGate';

const { t } = await useI18nGate('auth');

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <AuthLayout :description="t('auth.confirm_password.description')" :title="t('auth.confirm_password.title')">
        <Head :title="t('auth.confirm_password.title')" />

        <form @submit.prevent="submit">
            <div class="space-y-6">
                <div class="grid gap-2">
                    <Label htmlFor="password">{{ t('auth.confirm_password.password') }}</Label>
                    <Input
                        id="password"
                        v-model="form.password"
                        autocomplete="current-password"
                        autofocus
                        class="mt-1 block w-full"
                        required
                        type="password"
                    />

                    <InputError :message="form.errors.password" />
                </div>

                <div class="flex items-center">
                    <Button :disabled="form.processing" class="w-full">
                        <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                        {{ t('auth.confirm_password.submit') }}
                    </Button>
                </div>
            </div>
        </form>
    </AuthLayout>
</template>
