<script lang="ts" setup>
import PublicNavbar from '@/components/PublicNavbar.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Toaster } from '@/components/ui/toast';
import { useToast } from '@/composables/useToast';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    mode: 'subscribe' | 'manage';
    blogs: Array<{ id: number; name: string; slug: string }>;
    translations: any;
    // subscribe
    selectedBlogId?: number | string;
    userEmail?: string;
    // manage
    email?: string;
    currentSubscriptions?: Array<{ blog_id: number; frequency: string; send_time: string | null; send_day: number | null }>;
    updateUrl?: string;
    unsubscribeUrl?: string;
    config: {
        daily_weekday_time: string;
        daily_weekend_time: string;
        weekly_day: number;
        weekly_time: string;
    };
}>();

const isManageMode = computed(() => props.mode === 'manage');
const displayEmail = computed(() => (props.mode === 'subscribe' ? props.userEmail || '' : props.email || ''));

const getInitialSub = (blogId: number) => {
    const existing = props.currentSubscriptions?.find((s) => s.blog_id === blogId);
    if (existing) {
        return {
            blog_id: blogId,
            selected: true,
            frequency: existing.frequency,
            send_time: existing.send_time || props.config.weekly_time,
            send_day: existing.send_day || props.config.weekly_day,
        };
    }
    const isInitiallySelected = props.selectedBlogId && Number(props.selectedBlogId) === blogId;
    return {
        blog_id: blogId,
        selected: !!isInitiallySelected,
        frequency: 'weekly',
        send_time: props.config.weekly_time,
        send_day: props.config.weekly_day,
    };
};

const newsletterForm = useForm({
    email: displayEmail.value,
    subscriptions: props.blogs.map((blog) => getInitialSub(blog.id)),
});

const unsubscribeForm = useForm({ email: displayEmail.value });

const hasSelectedBlogs = computed(() => newsletterForm.subscriptions.some((s) => s.selected));

const { toast } = useToast();

const t = computed(() => props.translations.messages);

const submitUrl = computed(() => (isManageMode.value ? props.updateUrl! : route('newsletter.store')));
const submitText = computed(() =>
    isManageMode.value
        ? newsletterForm.processing
            ? t.value.form.submitting
            : t.value.form.submit_manage
        : newsletterForm.processing
          ? t.value.form.submitting
          : t.value.form.submit_subscribe,
);
const title = computed(() => (isManageMode.value ? t.value.title.manage : t.value.title.subscribe));
const desc = computed(() => (isManageMode.value ? t.value.description.manage.replace('{email}', displayEmail.value) : t.value.description.subscribe));
const blogLabel = computed(() => t.value.form.blog_label);
const successDesc = computed(() => (isManageMode.value ? t.value.messages.success_manage : t.value.messages.success_subscribe));

const submit = () => {
    if (hasSelectedBlogs.value || isManageMode.value) {
        // We use post but with the filtered payload. Inertia's useForm doesn't easily allow filtering data on submit without manually setting it.
        // But we can just pass the whole thing and the backend will handle it, or we can adjust how we store data in form.
        // Let's keep it simple: the backend expects 'subscriptions' array.
        const originalSubscriptions = newsletterForm.subscriptions;
        newsletterForm.subscriptions = newsletterForm.subscriptions.filter((s) => s.selected);

        newsletterForm.post(submitUrl.value, {
            onSuccess: () => {
                newsletterForm.subscriptions = originalSubscriptions;
                toast({
                    title: t.value.messages.success_title,
                    description: successDesc.value,
                    variant: 'success',
                    size: 'sm',
                });
            },
            onError: () => {
                newsletterForm.subscriptions = originalSubscriptions;
                toast({
                    title: t.value.messages.error_title,
                    description: isManageMode.value ? t.value.messages.error_manage : t.value.messages.error_subscribe,
                    variant: 'destructive',
                    size: 'sm',
                });
            },
        });
    }
};

const unsubscribe = () => {
    if (confirm(t.value.form.unsubscribe_confirm)) {
        unsubscribeForm.post(props.unsubscribeUrl!, {
            onSuccess: () => {
                toast({
                    title: t.value.messages.unsubscribed_title,
                    description: t.value.messages.unsubscribed,
                    variant: 'success',
                    size: 'sm',
                });
            },
        });
    }
};
</script>

<template>
    <Head :title="title" />
    <div class="flex min-h-screen flex-col bg-primary-foreground text-primary">
        <PublicNavbar maxWidth="max-w-screen-lg" />
        <Toaster />
        <main class="mx-auto w-full max-w-screen-lg p-4 sm:px-12 md:px-16">
            <div class="mx-auto max-w-2xl py-12">
                <h1 class="mb-6 text-3xl font-bold text-accent-foreground">
                    {{ title }}
                </h1>
                <p class="mb-8 text-slate-600 dark:text-slate-400" v-html="desc"></p>

                <form class="space-y-8" @submit.prevent="submit">
                    <!-- Email sekcja tylko w subscribe -->
                    <div v-if="!isManageMode" class="space-y-2">
                        <Label class="text-slate-700 dark:text-slate-300" for="email">{{ t.form.email_label }}</Label>
                        <Input id="email" v-model="newsletterForm.email" placeholder="email@example.com" required type="email" />
                        <p v-if="newsletterForm.errors.email" class="text-sm text-error">{{ newsletterForm.errors.email }}</p>
                    </div>

                    <!-- Blogi -->
                    <div class="space-y-4">
                        <Label class="text-slate-700 dark:text-slate-300">{{ blogLabel }}</Label>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="border-b border-border bg-muted/50 text-xs text-slate-500 uppercase dark:text-slate-400">
                                    <tr>
                                        <th class="px-4 py-3 font-medium"></th>
                                        <th class="px-4 py-3 font-medium">{{ t.form.blog_name }}</th>
                                        <th class="px-4 py-3 font-medium">{{ t.form.frequency }}</th>
                                        <th class="px-4 py-3 font-medium">{{ t.form.schedule }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border">
                                    <tr v-for="sub in newsletterForm.subscriptions" :key="sub.blog_id" class="hover:bg-muted/30">
                                        <td class="px-4 py-3">
                                            <Checkbox :id="'blog-' + sub.blog_id" v-model:checked="sub.selected" class="text-primary" />
                                        </td>
                                        <td class="px-4 py-3 font-medium">
                                            <label :for="'blog-' + sub.blog_id" class="cursor-pointer">
                                                {{ blogs.find((b) => b.id === sub.blog_id)?.name }}
                                            </label>
                                        </td>
                                        <td class="px-4 py-3">
                                            <select
                                                v-model="sub.frequency"
                                                :disabled="!sub.selected"
                                                class="rounded-md border border-input bg-background px-2 py-1 text-sm ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none disabled:opacity-50"
                                            >
                                                <option value="daily">{{ t.form.daily }}</option>
                                                <option value="weekly">{{ t.form.weekly }}</option>
                                            </select>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div v-if="sub.selected && sub.frequency === 'weekly'" class="flex items-center gap-2">
                                                <select
                                                    v-model="sub.send_day"
                                                    class="rounded-md border border-input bg-background px-2 py-1 text-sm ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none"
                                                >
                                                    <option :value="1">{{ t.form.monday }}</option>
                                                    <option :value="2">{{ t.form.tuesday }}</option>
                                                    <option :value="3">{{ t.form.wednesday }}</option>
                                                    <option :value="4">{{ t.form.thursday }}</option>
                                                    <option :value="5">{{ t.form.friday }}</option>
                                                    <option :value="6">{{ t.form.saturday }}</option>
                                                    <option :value="7">{{ t.form.sunday }}</option>
                                                </select>
                                                <input
                                                    v-model="sub.send_time"
                                                    class="w-20 rounded-md border border-input bg-background px-2 py-1 text-sm ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none"
                                                    type="time"
                                                />
                                            </div>
                                            <span v-else-if="sub.selected && sub.frequency === 'daily'" class="text-xs text-slate-500"> - </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-if="newsletterForm.errors.subscriptions" class="text-sm text-error">{{ newsletterForm.errors.subscriptions }}</p>
                    </div>

                    <!-- Buttons -->
                    <div class="flex flex-col gap-4">
                        <Button
                            :disabled="newsletterForm.processing || (!hasSelectedBlogs && !isManageMode)"
                            :variant="hasSelectedBlogs ? 'outline' : 'muted'"
                            class="w-full"
                            type="submit"
                        >
                            {{ submitText }}
                        </Button>
                        <Button
                            v-if="isManageMode"
                            class="w-full text-destructive hover:bg-destructive/10"
                            type="button"
                            variant="ghost"
                            @click="unsubscribe"
                        >
                            {{ t.form.unsubscribe }}
                        </Button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</template>
