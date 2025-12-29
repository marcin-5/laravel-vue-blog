<script lang="ts" setup>
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useI18nGate } from '@/composables/useI18nGate';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

const { t } = await useI18nGate('auth');

const props = defineProps<{
    registrationEnabled: boolean;
}>();

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    if (!props.registrationEnabled) return;
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head :title="t('auth.register.title')" />

    <AuthBase
        :description="props.registrationEnabled ? t('auth.register.description') : t('auth.register.unavailable_description')"
        :title="props.registrationEnabled ? t('auth.register.title') : t('auth.register.unavailable_title')"
    >
        <template v-if="props.registrationEnabled">
            <form class="flex flex-col gap-6" @submit.prevent="submit">
                <div class="grid gap-6">
                    <div class="grid gap-2">
                        <Label for="name">{{ t('auth.register.name') }}</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            :placeholder="t('auth.register.name_placeholder')"
                            :tabindex="1"
                            autofocus
                            required
                            type="text"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">{{ t('auth.register.email') }}</Label>
                        <Input
                            id="email"
                            v-model="form.email"
                            :placeholder="t('auth.register.email_placeholder')"
                            :tabindex="2"
                            required
                            type="email"
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password">{{ t('auth.register.password') }}</Label>
                        <Input
                            id="password"
                            v-model="form.password"
                            :placeholder="t('auth.register.password_placeholder')"
                            :tabindex="3"
                            required
                            type="password"
                        />
                        <InputError :message="form.errors.password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password_confirmation">{{ t('auth.register.password_confirmation') }}</Label>
                        <Input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            :placeholder="t('auth.register.password_confirmation_placeholder')"
                            :tabindex="4"
                            required
                            type="password"
                        />
                        <InputError :message="form.errors.password_confirmation" />
                    </div>

                    <Button :disabled="form.processing" class="mt-2 w-full" tabindex="5" type="submit">
                        <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                        {{ t('auth.register.submit') }}
                    </Button>
                </div>

                <div class="text-center text-sm text-muted-foreground">
                    {{ t('auth.register.already_have_account') }}
                    <TextLink :href="route('login')" :tabindex="6" class="underline underline-offset-4">
                        {{ t('auth.register.login_link') }}
                    </TextLink>
                </div>
            </form>
        </template>
        <template v-else>
            <div class="flex flex-col gap-2 text-center text-sm text-muted-foreground">
                <div>
                    {{ t('auth.register.already_have_account') }}
                    <TextLink :href="route('login')" class="font-light underline underline-offset-4">
                        {{ t('auth.register.login_link') }}
                    </TextLink>
                </div>
                <TextLink :href="route('home')" class="mt-4 underline underline-offset-4">
                    {{ t('auth.register.home_page_link') }}
                </TextLink>
            </div>
        </template>
    </AuthBase>
</template>
