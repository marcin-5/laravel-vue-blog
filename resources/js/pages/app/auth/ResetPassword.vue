<script lang="ts" setup>
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useI18nGate } from '@/composables/useI18nGate';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

const { t } = await useI18nGate('auth');

interface Props {
    token: string;
    email: string;
}

const props = defineProps<Props>();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <AuthLayout :description="t('auth.reset_password.description')" :title="t('auth.reset_password.title')">
        <Head :title="t('auth.reset_password.title')" />

        <form @submit.prevent="submit">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email">{{ t('auth.reset_password.email') }}</Label>
                    <Input id="email" v-model="form.email" autocomplete="email" class="mt-1 block w-full" name="email" readonly type="email" />
                    <InputError :message="form.errors.email" class="mt-2" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">{{ t('auth.reset_password.password') }}</Label>
                    <Input
                        id="password"
                        v-model="form.password"
                        :placeholder="t('auth.reset_password.password')"
                        autocomplete="new-password"
                        autofocus
                        class="mt-1 block w-full"
                        name="password"
                        type="password"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">{{ t('auth.reset_password.password_confirmation') }}</Label>
                    <Input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        :placeholder="t('auth.reset_password.password_confirmation')"
                        autocomplete="new-password"
                        class="mt-1 block w-full"
                        name="password_confirmation"
                        type="password"
                    />
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <Button :disabled="form.processing" class="mt-4 w-full" type="submit">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    {{ t('auth.reset_password.submit') }}
                </Button>
            </div>
        </form>
    </AuthLayout>
</template>
