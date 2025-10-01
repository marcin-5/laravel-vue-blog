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
    <Head :title="t('register.title')" />

    <AuthBase
        :description="props.registrationEnabled ? t('register.description') : t('register.unavailable_description')"
        :title="props.registrationEnabled ? t('register.title') : t('register.unavailable_title')"
    >
        <template v-if="props.registrationEnabled">
            <form class="flex flex-col gap-6" @submit.prevent="submit">
                <div class="grid gap-6">
                    <div class="grid gap-2">
                        <Label for="name">{{ t('register.name') }}</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            :placeholder="t('register.name_placeholder')"
                            :tabindex="1"
                            autocomplete="name"
                            autofocus
                            required
                            type="text"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">{{ t('register.email') }}</Label>
                        <Input
                            id="email"
                            v-model="form.email"
                            :placeholder="t('register.email_placeholder')"
                            :tabindex="2"
                            autocomplete="email"
                            required
                            type="email"
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password">{{ t('register.password') }}</Label>
                        <Input
                            id="password"
                            v-model="form.password"
                            :placeholder="t('register.password_placeholder')"
                            :tabindex="3"
                            autocomplete="new-password"
                            required
                            type="password"
                        />
                        <InputError :message="form.errors.password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password_confirmation">{{ t('register.password_confirmation') }}</Label>
                        <Input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            :placeholder="t('register.password_confirmation_placeholder')"
                            :tabindex="4"
                            autocomplete="new-password"
                            required
                            type="password"
                        />
                        <InputError :message="form.errors.password_confirmation" />
                    </div>

                    <Button :disabled="form.processing" class="mt-2 w-full" tabindex="5" type="submit">
                        <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                        {{ t('register.submit') }}
                    </Button>
                </div>

                <div class="text-center text-sm text-muted-foreground">
                    {{ t('register.already_have_account') }}
                    <TextLink :href="route('login')" :tabindex="6" class="underline underline-offset-4">
                        {{ t('register.login_link') }}
                    </TextLink>
                </div>
            </form>
        </template>
        <template v-else>
            <div class="flex flex-col gap-2 text-center text-sm text-muted-foreground">
                <div>
                    {{ t('register.already_have_account') }}
                    <TextLink :href="route('login')" class="font-light underline underline-offset-4">
                        {{ t('register.login_link') }}
                    </TextLink>
                </div>
                <TextLink :href="route('home')" class="mt-4 underline underline-offset-4">
                    {{ t('register.home_page_link') }}
                </TextLink>
            </div>
        </template>
    </AuthBase>
</template>
