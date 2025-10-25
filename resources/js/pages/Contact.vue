<script lang="ts" setup>
import PublicNavbar from '@/components/PublicNavbar.vue';
import SeoHead from '@/components/seo/SeoHead.vue';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

interface SeoProps {
    title?: string | null;
    description?: string | null;
    canonicalUrl?: string | null;
    ogImage?: string | null;
    ogType?: string | null;
    locale?: string | null;
    structuredData?: Record<string, any> | null;
}

const props = defineProps<{
    locale?: string | null;
    seo?: SeoProps | null;
}>();

const { t } = useI18n();

const title = computed(() => props.seo?.title ?? t('contact.meta.title', 'Contact'));
const description = computed(() => props.seo?.description ?? t('contact.meta.description', 'Get in touch'));
const canonicalUrl = computed(() => props.seo?.canonicalUrl ?? '');
const ogImage = computed(() => props.seo?.ogImage ?? null);
const ogType = computed(() => props.seo?.ogType ?? 'website');
const locale = computed(() => props.seo?.locale ?? props.locale ?? 'en');
const structuredData = computed(() => props.seo?.structuredData ?? null);

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
            // TODO: Replace alert with a non-disruptive toast notification or inline message.
            alert(t('contact.form.submitted', 'Your message has been sent.'));
        },
        onError: (errors) => {
            // 'errors' will be automatically populated in form.errors.
            // This alert is a fallback for non-validation errors.
            // TODO: Replace alert with a proper notification component.
            if (Object.keys(errors).length === 0) {
                alert(t('contact.form.error', 'Unable to send your message.'));
            }
        },
    });
}
</script>

<template>
    <SeoHead
        :canonical-url="canonicalUrl"
        :description="description"
        :locale="locale"
        :og-image="ogImage"
        :og-type="ogType"
        :structured-data="structuredData"
        :title="title"
    />

    <div class="flex min-h-screen flex-col">
        <PublicNavbar />

        <main class="mx-auto w-full max-w-[768px] p-6 lg:p-8">
            <h1 class="mb-6 font-serif text-3xl font-semibold text-shadow-stone-700 dark:text-shadow-stone-50">
                {{ t('contact.heading', 'Contact') }}
            </h1>

            <form class="grid gap-4" @submit="submit">
                <div>
                    <label class="mb-1 block text-sm font-medium" for="name">{{ t('contact.form.name', 'Name') }}</label>
                    <input
                        id="name"
                        v-model="form.name"
                        :placeholder="t('contact.form.placeholders.name', 'Your name')"
                        autocomplete="name"
                        class="w-full rounded-md border border-[#19140035] bg-white/70 p-2 text-sm text-[#1b1b18] placeholder:text-[#686862] focus:ring-2 focus:ring-[#19140035] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#262622] dark:text-[#EDEDEC] dark:placeholder:text-[#A1A1A1]"
                        required
                        type="text"
                        @keydown.stop
                    />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium" for="email">{{ t('contact.form.email', 'Email') }}</label>
                    <input
                        id="email"
                        v-model="form.email"
                        :placeholder="t('contact.form.placeholders.email', 'Your email')"
                        autocomplete="email"
                        class="w-full rounded-md border border-[#19140035] bg-white/70 p-2 text-sm text-[#1b1b18] placeholder:text-[#686862] focus:ring-2 focus:ring-[#19140035] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#262622] dark:text-[#EDEDEC] dark:placeholder:text-[#A1A1A1]"
                        required
                        type="email"
                        @keydown.stop
                    />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium" for="subject">{{ t('contact.form.subject', 'Subject') }}</label>
                    <input
                        id="subject"
                        v-model="form.subject"
                        :placeholder="t('contact.form.placeholders.subject', 'Subject')"
                        autocomplete="off"
                        class="w-full rounded-md border border-[#19140035] bg-white/70 p-2 text-sm text-[#1b1b18] placeholder:text-[#686862] focus:ring-2 focus:ring-[#19140035] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#262622] dark:text-[#EDEDEC] dark:placeholder:text-[#A1A1A1]"
                        required
                        type="text"
                        @keydown.stop
                    />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium" for="message">{{ t('contact.form.message', 'Message') }}</label>
                    <textarea
                        id="message"
                        v-model="form.message"
                        :placeholder="t('contact.form.placeholders.message', 'Write your message...')"
                        autocomplete="off"
                        class="w-full rounded-md border border-[#19140035] bg-white/70 p-2 text-sm text-[#1b1b18] placeholder:text-[#686862] focus:ring-2 focus:ring-[#19140035] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#262622] dark:text-[#EDEDEC] dark:placeholder:text-[#A1A1A1]"
                        required
                        rows="6"
                        @keydown.stop
                    />
                </div>

                <div class="mt-2">
                    <button
                        class="rounded-md border border-[#19140035] px-4 py-2 text-sm text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                        type="submit"
                    >
                        {{ t('contact.form.submit', 'Send message') }}
                    </button>
                </div>
            </form>
        </main>
    </div>
</template>
