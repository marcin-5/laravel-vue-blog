<script lang="ts" setup>
import NewsletterScheduleEditor from '@/components/newsletter/NewsletterScheduleEditor.vue';
import PublicHomeLayout from '@/layouts/PublicHomeLayout.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useNewsletterForm } from '@/composables/useNewsletterForm';
import type { Blog } from '@/types/blog.types';
import type { ExistingSubscription, NewsletterConfig, NewsletterTranslate } from '@/types/newsletter.types';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    mode: 'subscribe' | 'manage';
    blogs: Blog[];
    // subscribe
    selectedBlogId?: number | string;
    userEmail?: string;
    // manage
    email?: string;
    currentSubscriptions?: ExistingSubscription[];
    updateUrl?: string;
    unsubscribeUrl?: string;
    config: NewsletterConfig;
}>();

const { t } = useI18n();

const displayEmail = computed(() => (props.mode === 'subscribe' ? props.userEmail || '' : props.email || ''));
const { newsletterForm, isManageMode, hasSelectedBlogs, submit, unsubscribe } = useNewsletterForm({
    ...props,
    t: t as NewsletterTranslate,
});

const submitText = computed(() =>
    isManageMode.value
        ? newsletterForm.processing
            ? t('form.submitting')
            : t('form.submit_manage')
        : newsletterForm.processing
          ? t('form.submitting')
          : t('form.submit_subscribe'),
);
const title = computed(() => (isManageMode.value ? t('title.manage') : t('title.subscribe')));
const desc = computed(() => (isManageMode.value ? t('description.manage').replace('{email}', displayEmail.value) : t('description.subscribe')));
const blogLabel = computed(() => t('form.blog_label'));
</script>

<template>
    <PublicHomeLayout>
        <div class="mx-auto max-w-2xl py-12">
            <h1 class="mb-6 text-3xl font-bold text-accent-foreground">
                {{ title }}
            </h1>
            <p class="mb-8 text-slate-600 dark:text-slate-400" v-html="desc"></p>

            <form class="space-y-8" @submit.prevent="submit">
                <!-- Email sekcja tylko w subscribe -->
                <div v-if="!isManageMode" class="space-y-2">
                    <Label class="text-slate-700 dark:text-slate-300" for="email">{{ t('form.email_label') }}</Label>
                    <Input id="email" v-model="newsletterForm.email" placeholder="email@example.com" required type="email" />
                    <p v-if="newsletterForm.errors.email" class="text-sm text-error">{{ newsletterForm.errors.email }}</p>
                </div>

                <!-- Blogi -->
                <div class="space-y-4">
                    <Label class="text-slate-700 dark:text-slate-300">{{ blogLabel }}</Label>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-border bg-muted/50 text-xs text-primary uppercase">
                                <tr>
                                    <th class="px-4 py-3 font-medium"></th>
                                    <th class="px-4 py-3 font-medium">{{ t('form.blog_name') }}</th>
                                    <th class="px-4 py-3 font-medium">{{ t('form.frequency') }}</th>
                                    <th class="px-4 py-3 font-medium">{{ t('form.schedule') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr v-for="(sub, idx) in newsletterForm.subscriptions" :key="sub.blog_id" class="hover:bg-muted/30">
                                    <td class="px-4 py-3">
                                        <Checkbox
                                            :id="'blog-' + sub.blog_id"
                                            :model-value="sub.selected"
                                            class="text-foreground"
                                            @update:model-value="sub.selected = !!$event"
                                        />
                                    </td>
                                    <td class="px-4 py-3 font-medium">
                                        <label :for="'blog-' + sub.blog_id" class="cursor-pointer">
                                            {{ blogs.find((b) => b.id === sub.blog_id)?.name }}
                                        </label>
                                    </td>
                                    <td class="px-4 py-3">
                                        <Select
                                            v-model="sub.frequency"
                                            :disabled="!sub.selected"
                                            @update:model-value="
                                                $event === 'daily' && !sub.send_time_weekend
                                                    ? (sub.send_time_weekend = props.config.daily_weekend_time)
                                                    : null
                                            "
                                        >
                                            <SelectTrigger class="h-8 w-25">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="daily">{{ t('form.daily') }}</SelectItem>
                                                <SelectItem value="weekly">{{ t('form.weekly') }}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </td>
                                    <td class="px-4 py-3">
                                        <NewsletterScheduleEditor v-if="sub.selected" v-model="newsletterForm.subscriptions[idx]" :t="t" />
                                        <span v-else class="text-xs text-slate-500"> - </span>
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
                        {{ t('form.unsubscribe') }}
                    </Button>
                </div>
            </form>
        </div>
    </PublicHomeLayout>
</template>
