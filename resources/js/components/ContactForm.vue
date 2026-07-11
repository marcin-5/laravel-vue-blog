<script lang="ts" setup>
import { useToast } from '@/composables/useToast';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    submitRoute: string;
    recipientName?: string;
}>();

const { t } = useI18n();
const { toast } = useToast();

const INPUT_CLASSES =
    'w-full rounded-md border border-border bg-background/70 p-2 text-sm text-foreground placeholder:text-muted-foreground focus:ring-2 focus:ring-ring/30 focus:outline-none';
const LABEL_CLASSES = 'mb-1 block text-sm font-medium';
const BUTTON_CLASSES = 'rounded-md border border-border px-4 py-2 text-sm text-foreground hover:border-ring/40';

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
        <form class="grid gap-4" @submit.prevent="submit">
            <div>
                <label :class="LABEL_CLASSES" for="contact-name">{{ t('contact.form.name', 'Name') }}</label>
                <input
                    id="contact-name"
                    v-model="form.name"
                    :class="INPUT_CLASSES"
                    :placeholder="t('contact.form.placeholders.name', 'Your name')"
                    autocomplete="name"
                    data-gramm="false"
                    data-lt-active="false"
                    required
                    spellcheck="false"
                    type="text"
                    @keydown.stop
                />
                <p v-if="form.errors.name" class="mt-1 text-xs text-destructive">{{ form.errors.name }}</p>
            </div>
            <div>
                <label :class="LABEL_CLASSES" for="contact-email">{{ t('contact.form.email', 'Email') }}</label>
                <input
                    id="contact-email"
                    v-model="form.email"
                    :class="INPUT_CLASSES"
                    :placeholder="t('contact.form.placeholders.email', 'Your email')"
                    autocomplete="email"
                    data-gramm="false"
                    data-lt-active="false"
                    required
                    spellcheck="false"
                    type="email"
                    @keydown.stop
                />
                <p v-if="form.errors.email" class="mt-1 text-xs text-destructive">{{ form.errors.email }}</p>
            </div>
            <div>
                <label :class="LABEL_CLASSES" for="contact-subject">{{ t('contact.form.subject', 'Subject') }}</label>
                <input
                    id="contact-subject"
                    v-model="form.subject"
                    :class="INPUT_CLASSES"
                    :placeholder="t('contact.form.placeholders.subject', 'Subject')"
                    autocomplete="off"
                    data-gramm="false"
                    data-lt-active="false"
                    required
                    spellcheck="false"
                    type="text"
                    @keydown.stop
                />
                <p v-if="form.errors.subject" class="mt-1 text-xs text-destructive">{{ form.errors.subject }}</p>
            </div>
            <div>
                <label :class="LABEL_CLASSES" for="contact-message">{{ t('contact.form.message', 'Message') }}</label>
                <textarea
                    id="contact-message"
                    v-model="form.message"
                    :class="INPUT_CLASSES"
                    :placeholder="t('contact.form.placeholders.message', 'Write your message...')"
                    autocomplete="off"
                    data-gramm="false"
                    data-lt-active="false"
                    required
                    rows="6"
                    spellcheck="false"
                    @keydown.stop
                />
                <p v-if="form.errors.message" class="mt-1 text-xs text-destructive">{{ form.errors.message }}</p>
            </div>
            <div class="mt-2">
                <button :class="BUTTON_CLASSES" :disabled="form.processing" type="submit">
                    {{ t('contact.form.submit', 'Send message') }}
                </button>
            </div>
        </form>
    </div>
</template>
