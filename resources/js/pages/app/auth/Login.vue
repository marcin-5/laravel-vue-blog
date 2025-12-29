<script lang="ts" setup>
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useI18nGate } from '@/composables/useI18nGate';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

const { t } = await useI18nGate('auth');

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <AuthBase :description="t('auth.login.description')" :title="t('auth.login.title')">
        <Head :title="t('auth.login.title')" />

        <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <form class="flex flex-col gap-6" @submit.prevent="submit">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email">{{ t('auth.login.email') }}</Label>
                    <Input
                        id="email"
                        v-model="form.email"
                        :tabindex="1"
                        autocomplete="email"
                        autofocus
                        placeholder="email@example.com"
                        required
                        type="email"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label for="password">{{ t('auth.login.password') }}</Label>
                        <TextLink v-if="canResetPassword" :href="route('password.request')" :tabindex="5" class="text-sm">
                            {{ t('auth.login.forgot_password') }}
                        </TextLink>
                    </div>
                    <Input
                        id="password"
                        v-model="form.password"
                        :placeholder="t('auth.login.password')"
                        :tabindex="2"
                        autocomplete="current-password"
                        required
                        type="password"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="flex items-center justify-between">
                    <Label class="flex items-center space-x-3" for="remember">
                        <Checkbox id="remember" v-model="form.remember" :tabindex="3" />
                        <span>{{ t('auth.login.remember') }}</span>
                    </Label>
                </div>

                <Button :disabled="form.processing" :tabindex="4" class="mt-4 w-full" type="submit">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    {{ t('auth.login.submit') }}
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                {{ t('auth.login.no_account') }}
                <TextLink :href="route('register')" :tabindex="5">{{ t('auth.login.signup') }}</TextLink>
            </div>
        </form>
    </AuthBase>
</template>
