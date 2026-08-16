import { useToast } from '@/composables/useToast';
import type { Blog } from '@/types/blog.types';
import type {
    ExistingSubscription,
    NewsletterConfig,
    NewsletterFormData,
    NewsletterSubscription,
    NewsletterSubscriptionPayload,
    NewsletterTranslate,
} from '@/types/newsletter.types';
import { useForm, type InertiaForm } from '@inertiajs/vue3';
import { computed } from 'vue';

interface UseNewsletterFormOptions {
    mode: 'subscribe' | 'manage';
    blogs: Blog[];
    config: NewsletterConfig;
    selectedBlogId?: number | string;
    userEmail?: string;
    email?: string;
    currentSubscriptions?: ExistingSubscription[];
    updateUrl?: string;
    unsubscribeUrl?: string;
    t: NewsletterTranslate;
}

function initialSubscription(options: UseNewsletterFormOptions, blogId: number): NewsletterSubscription {
    const existing = options.currentSubscriptions?.find((subscription) => subscription.blog_id === blogId);

    if (existing) {
        return {
            blog_id: blogId,
            selected: true,
            frequency: existing.frequency as 'daily' | 'weekly',
            send_time: existing.send_time || (existing.frequency === 'daily' ? options.config.daily_weekday_time : options.config.weekly_time),
            send_time_weekend: existing.send_time_weekend || (existing.frequency === 'daily' ? options.config.daily_weekend_time : null),
            send_day: existing.send_day || options.config.weekly_day,
        };
    }

    const isInitiallySelected = options.selectedBlogId && Number(options.selectedBlogId) === blogId;

    return {
        blog_id: blogId,
        selected: !!isInitiallySelected,
        frequency: 'weekly',
        send_time: options.config.weekly_time,
        send_time_weekend: null,
        send_day: options.config.weekly_day,
    };
}

function toPayload(subscription: NewsletterSubscription): NewsletterSubscriptionPayload {
    return {
        blog_id: subscription.blog_id,
        frequency: subscription.frequency,
        send_time: subscription.send_time,
        send_time_weekend: subscription.send_time_weekend,
        send_day: subscription.send_day,
    };
}

export function useNewsletterForm(options: UseNewsletterFormOptions) {
    const displayEmail = computed(() => (options.mode === 'subscribe' ? options.userEmail || '' : options.email || ''));
    const newsletterForm: InertiaForm<NewsletterFormData> = useForm<NewsletterFormData>({
        email: displayEmail.value,
        subscriptions: options.blogs.map((blog) => initialSubscription(options, blog.id)),
    });
    const unsubscribeForm: InertiaForm<{ email: string }> = useForm<{ email: string }>({ email: displayEmail.value });
    const isManageMode = computed(() => options.mode === 'manage');
    const hasSelectedBlogs = computed(() => newsletterForm.subscriptions.some((subscription) => subscription.selected));
    const submitUrl = computed(() => (isManageMode.value ? options.updateUrl || '' : route('newsletter.store')));
    const { toast } = useToast();

    const submit = (): void => {
        if (!hasSelectedBlogs.value && !isManageMode.value) {
            return;
        }

        newsletterForm
            .transform((data) => ({
                email: data.email,
                subscriptions: data.subscriptions.filter((subscription) => subscription.selected).map(toPayload),
            }))
            .post(submitUrl.value, {
                onSuccess: () => {
                    toast({
                        title: options.t('messages.success_title'),
                        description: isManageMode.value ? options.t('messages.success_manage') : options.t('messages.success_subscribe'),
                        variant: 'success',
                        size: 'sm',
                    });
                },
                onError: () => {
                    toast({
                        title: options.t('messages.error_title'),
                        description: isManageMode.value ? options.t('messages.error_manage') : options.t('messages.error_subscribe'),
                        variant: 'destructive',
                        size: 'sm',
                    });
                },
            });
    };

    const unsubscribe = (): void => {
        if (!window.confirm(options.t('form.unsubscribe_confirm'))) {
            return;
        }

        unsubscribeForm.post(options.unsubscribeUrl || '', {
            onSuccess: () => {
                toast({
                    title: options.t('messages.unsubscribed_title'),
                    description: options.t('messages.unsubscribed'),
                    variant: 'success',
                    size: 'sm',
                });
            },
        });
    };

    return {
        newsletterForm,
        unsubscribeForm,
        isManageMode,
        hasSelectedBlogs,
        submitUrl,
        submit,
        unsubscribe,
    };
}
