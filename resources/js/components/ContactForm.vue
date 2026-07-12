<script lang="ts" setup>
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useToast } from '@/composables/useToast';
import { useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    submitRoute: string;
    recipientName?: string;
}>();

const { t } = useI18n();
const { toast } = useToast();

const form = useForm({
    name: '',
    email: '',
    subject: '',
    message: '',
});

function submit() {
    form.post(props.submitRoute, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            toast({
                title: t('contact.form.submitted', 'Your message has been sent.'),
                variant: 'success',
            });
        },
        onError: (errors) => {
            if (Object.keys(errors).length === 0) {
                toast({
                    title: t('contact.form.error', 'Unable to send your message.'),
                    variant: 'destructive',
                });
            }
        },
    });
}
</script>

<template>
    <div>
        <p v-if="recipientName" class="mb-4 text-sm text-muted-foreground">
            {{ t('contact.form.recipient', 'Your message will be sent to:') }}
            <strong class="text-foreground">{{ recipientName }}</strong>
        </p>
        <form class="flex flex-col gap-6" @submit.prevent="submit">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="contact-name">{{ t('contact.form.name', 'Name') }}</Label>
                    <Input
                        id="contact-name"
                        name="name"
                        v-model="form.name"
                        :placeholder="t('contact.form.placeholders.name', 'Your name')"
                        autocomplete="name"
                        required
                        type="text"
                    />
                    <InputError :message="form.errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="contact-email">{{ t('contact.form.email', 'Email') }}</Label>
                    <Input
                        id="contact-email"
                        name="email"
                        v-model="form.email"
                        :placeholder="t('contact.form.placeholders.email', 'Your email')"
                        autocomplete="email"
                        required
                        type="email"
                    />
                    <InputError :message="form.errors.email" />
                </div>
                <div class="grid gap-2">
                    <Label for="contact-subject">{{ t('contact.form.subject', 'Subject') }}</Label>
                    <Input
                        id="contact-subject"
                        name="subject"
                        v-model="form.subject"
                        :placeholder="t('contact.form.placeholders.subject', 'Subject')"
                        autocomplete="off"
                        required
                        type="text"
                    />
                    <InputError :message="form.errors.subject" />
                </div>
                <div class="grid gap-2">
                    <Label for="contact-message">{{ t('contact.form.message', 'Message') }}</Label>
                    <textarea
                        id="contact-message"
                        name="message"
                        v-model="form.message"
                        :placeholder="t('contact.form.placeholders.message', 'Write your message...')"
                        autocomplete="off"
                        class="flex min-h-30 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:ring-[3px] focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                        required
                        rows="6"
                    />
                    <InputError :message="form.errors.message" />
                </div>
                <Button :disabled="form.processing" class="mt-2 w-full" type="submit">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    {{ t('contact.form.submit', 'Send message') }}
                </Button>
            </div>
        </form>
    </div>
</template>
