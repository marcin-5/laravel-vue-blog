<script lang="ts" setup>
import PublicHomeLayout from '@/layouts/PublicHomeLayout.vue';
import { useToast } from '@/composables/useToast';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { toast } = useToast();

const INPUT_CLASSES =
    'w-full rounded-md border border-[#19140035] bg-white/70 p-2 text-sm text-[#1b1b18] placeholder:text-[#686862] focus:ring-2 focus:ring-[#19140035] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#262622] dark:text-[#EDEDEC] dark:placeholder:text-[#A1A1A1]';
const LABEL_CLASSES = 'mb-1 block text-sm font-medium';
const BUTTON_CLASSES =
    'rounded-md border border-[#19140035] px-4 py-2 text-sm text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]';

const form = useForm({
    name: '',
    email: '',
    subject: '',
    message: '',
});

function submit() {
    form.post(route('public.contact.submit'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            toast({
                title: t('contact.form.submitted', 'Your message has been sent.'),
                variant: 'success',
            });
        },
        onError: (errors) => {
            // 'errors' will be automatically populated in form.errors.
            // This toast is a fallback for non-validation errors.
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
    <PublicHomeLayout maxWidth="max-w-[768px]">
        <h1 class="mb-6 font-serif text-3xl font-semibold text-shadow-stone-700 dark:text-shadow-stone-50">
            {{ t('contact.heading', 'Contact') }}
        </h1>
        <form class="grid gap-4" @submit.prevent="submit">
            <div>
                <label :class="LABEL_CLASSES" for="name">{{ t('contact.form.name', 'Name') }}</label>
                <input
                    id="name"
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
            </div>
            <div>
                <label :class="LABEL_CLASSES" for="email">{{ t('contact.form.email', 'Email') }}</label>
                <input
                    id="email"
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
            </div>
            <div>
                <label :class="LABEL_CLASSES" for="subject">{{ t('contact.form.subject', 'Subject') }}</label>
                <input
                    id="subject"
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
            </div>
            <div>
                <label :class="LABEL_CLASSES" for="message">{{ t('contact.form.message', 'Message') }}</label>
                <textarea
                    id="message"
                    v-model="form.message"
                    :class="INPUT_CLASSES"
                    :placeholder="t('contact.form.placeholders.message', 'Write your message...')"
                    autocomplete="off"
                    data-gramm="false"
                    data-lt-active="false"
                    required
                    spellcheck="false"
                    rows="6"
                    @keydown.stop
                />
            </div>
            <div class="mt-2">
                <button :class="BUTTON_CLASSES" type="submit">
                    {{ t('contact.form.submit', 'Send message') }}
                </button>
            </div>
        </form>
    </PublicHomeLayout>
</template>
