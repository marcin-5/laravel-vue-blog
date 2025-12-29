<script lang="ts" setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

import { useI18nNs } from '@/composables/useI18nNs';
// Components
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const { t } = await useI18nNs(['profile', 'common']);

const passwordInput = ref<HTMLInputElement | null>(null);

const form = useForm({
    password: '',
});

const deleteUser = (e: Event) => {
    e.preventDefault();

    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value?.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    form.clearErrors();
    form.reset();
};
</script>

<template>
    <div class="space-y-6">
        <HeadingSmall :description="t('settings.profile.delete_account.description')" :title="t('settings.profile.delete_account.title')" />
        <div class="space-y-4 rounded-lg border border-b-destructive-hover bg-destructive-foreground p-4">
            <div class="relative space-y-0.5 text-destructive">
                <p class="font-medium">{{ t('common.warning') }}</p>
                <p class="text-sm font-semibold">{{ t('common.caution_message') }}</p>
            </div>
            <Dialog>
                <DialogTrigger as-child>
                    <Button variant="destructive">{{ t('settings.profile.delete_account.button') }}</Button>
                </DialogTrigger>
                <DialogContent>
                    <form class="space-y-6" @submit="deleteUser">
                        <DialogHeader class="space-y-3">
                            <DialogTitle>{{ t('settings.profile.delete_account.confirm_title') }}</DialogTitle>
                            <DialogDescription>
                                {{ t('settings.profile.delete_account.confirm_description') }}
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label class="sr-only" for="password">{{ t('settings.profile.delete_account.password_placeholder') }}</Label>
                            <Input
                                id="password"
                                ref="passwordInput"
                                v-model="form.password"
                                :placeholder="t('settings.profile.delete_account.password_placeholder')"
                                name="password"
                                type="password"
                            />
                            <InputError :message="form.errors.password" />
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button variant="secondary" @click="closeModal"> {{ t('common.cancel') }} </Button>
                            </DialogClose>

                            <Button :disabled="form.processing" type="submit" variant="destructive">
                                {{ t('settings.profile.delete_account.button') }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>
